import {
  Injectable,
  NotFoundException,
  ConflictException,
  BadRequestException,
  Logger,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import * as bcrypt from 'bcrypt';
import { User } from '@/database/entities/user.entity';
import { S3Service } from '@/shared/s3/s3.service';
import { CreateUserDto } from './dto/create-user.dto';
import { UpdateUserDto } from './dto/update-user.dto';
import { UserFilterDto } from './dto/user-filter.dto';
import { PaginatedResponseDto } from '@/common/dto/response.dto';

// Número de rondas para el hashing de contraseñas con bcrypt
const BCRYPT_SALT_ROUNDS = 12;

@Injectable()
export class UsersService {
  private readonly logger = new Logger(UsersService.name);

  constructor(
    @InjectRepository(User)
    private readonly userRepo: Repository<User>,
    private readonly s3Service: S3Service,
  ) {}

  /**
   * Obtener lista paginada de usuarios con filtros opcionales.
   * Utiliza QueryBuilder para construir consultas dinámicas.
   */
  async findAll(filter: UserFilterDto): Promise<PaginatedResponseDto<User>> {
    const { page = 1, limit = 20, roleId, departmentId, isActive, search } = filter;

    const qb = this.userRepo
      .createQueryBuilder('user')
      .leftJoinAndSelect('user.role', 'role')
      .leftJoinAndSelect('user.department', 'department');

    // Filtrar por rol
    if (roleId) {
      qb.andWhere('user.roleId = :roleId', { roleId });
    }

    // Filtrar por departamento
    if (departmentId) {
      qb.andWhere('user.departmentId = :departmentId', { departmentId });
    }

    // Filtrar por estado activo/inactivo
    if (isActive !== undefined) {
      qb.andWhere('user.isActive = :isActive', { isActive });
    }

    // Búsqueda por nombre, apellido o correo electrónico (case-insensitive)
    if (search) {
      qb.andWhere(
        '(LOWER(user.firstName) LIKE LOWER(:search) OR LOWER(user.lastName) LIKE LOWER(:search) OR LOWER(user.email) LIKE LOWER(:search))',
        { search: `%${search}%` },
      );
    }

    // Ordenar por fecha de creación descendente
    qb.orderBy('user.createdAt', 'DESC');

    // Paginación
    qb.skip(filter.skip).take(limit);

    const [users, total] = await qb.getManyAndCount();

    this.logger.log(`Listando usuarios: ${total} encontrados (página ${page})`);

    return PaginatedResponseDto.paginated<User>(users, total, page, limit);
  }

  /**
   * Buscar un usuario por su ID, incluyendo relaciones con rol y departamento.
   * Lanza NotFoundException si el usuario no existe.
   */
  async findOne(id: string): Promise<User> {
    const user = await this.userRepo.findOne({
      where: { id },
      relations: ['role', 'department'],
    });

    if (!user) {
      throw new NotFoundException(`Usuario con ID ${id} no encontrado`);
    }

    return user;
  }

  /**
   * Crear un nuevo usuario.
   * Hashea la contraseña antes de almacenarla y verifica que el email no esté duplicado.
   */
  async create(dto: CreateUserDto): Promise<User> {
    // Verificar que el email no esté en uso
    const existingUser = await this.userRepo.findOne({
      where: { email: dto.email },
    });

    if (existingUser) {
      throw new ConflictException(`Ya existe un usuario con el correo ${dto.email}`);
    }

    // Hashear la contraseña con bcrypt (12 rondas)
    const hashedPassword = await bcrypt.hash(dto.password, BCRYPT_SALT_ROUNDS);

    const user = this.userRepo.create({
      ...dto,
      password: hashedPassword,
    });

    const savedUser = await this.userRepo.save(user);

    this.logger.log(`Usuario creado: ${savedUser.email} (ID: ${savedUser.id})`);

    // Retornar el usuario con sus relaciones cargadas
    return this.findOne(savedUser.id);
  }

  /**
   * Actualizar un usuario existente.
   * Si se proporciona contraseña, se hashea antes de guardar.
   * Verifica unicidad del email si se modifica.
   */
  async update(id: string, dto: UpdateUserDto): Promise<User> {
    const user = await this.findOne(id);

    // Si se cambia el email, verificar que no esté en uso por otro usuario
    if (dto.email && dto.email !== user.email) {
      const existingUser = await this.userRepo.findOne({
        where: { email: dto.email },
      });

      if (existingUser) {
        throw new ConflictException(`Ya existe un usuario con el correo ${dto.email}`);
      }
    }

    // Si se proporciona contraseña, hashearla
    const updateData: Partial<User> = { ...dto };
    if (dto.password) {
      updateData.password = await bcrypt.hash(dto.password, BCRYPT_SALT_ROUNDS);
    }

    await this.userRepo.update(id, updateData);

    this.logger.log(`Usuario actualizado: ${user.email} (ID: ${id})`);

    // Retornar el usuario actualizado con relaciones
    return this.findOne(id);
  }

  /**
   * Eliminar un usuario mediante soft delete.
   * El registro permanece en la base de datos con deletedAt marcado.
   */
  async remove(id: string): Promise<void> {
    const user = await this.findOne(id);

    await this.userRepo.softDelete(id);

    this.logger.log(`Usuario eliminado (soft delete): ${user.email} (ID: ${id})`);
  }

  /**
   * Subir avatar de usuario a S3 y actualizar la URL en el registro del usuario.
   * Si el usuario ya tenía un avatar, se elimina el anterior de S3.
   */
  async uploadAvatar(id: string, file: Express.Multer.File): Promise<User> {
    const user = await this.findOne(id);

    if (!file) {
      throw new BadRequestException('No se proporcionó un archivo de imagen');
    }

    // Si ya existe un avatar anterior, eliminarlo de S3
    if (user.avatarUrl) {
      try {
        await this.s3Service.deleteFile(user.avatarUrl);
      } catch (error) {
        this.logger.warn(`No se pudo eliminar el avatar anterior: ${user.avatarUrl}`, error);
      }
    }

    // Subir el nuevo avatar a S3 en la carpeta 'avatars'
    const avatarUrl = await this.s3Service.uploadFile(file, 'avatars');

    // Actualizar la URL del avatar en el usuario
    await this.userRepo.update(id, { avatarUrl });

    this.logger.log(`Avatar actualizado para usuario: ${user.email} (ID: ${id})`);

    return this.findOne(id);
  }
}
