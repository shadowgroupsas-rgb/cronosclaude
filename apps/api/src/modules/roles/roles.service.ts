import {
  Injectable,
  NotFoundException,
  ForbiddenException,
  ConflictException,
  Logger,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Role } from '@/database/entities/role.entity';
import { CreateRoleDto } from './dto/create-role.dto';
import { UpdateRoleDto } from './dto/update-role.dto';

/**
 * Servicio encargado de la gestión de roles del sistema.
 * Maneja operaciones CRUD respetando la protección de roles de sistema.
 */
@Injectable()
export class RolesService {
  private readonly logger = new Logger(RolesService.name);

  constructor(
    @InjectRepository(Role) private readonly roleRepo: Repository<Role>,
  ) {}

  /**
   * Obtiene todos los roles registrados en el sistema.
   * @returns Lista completa de roles ordenados por fecha de creación
   */
  async findAll(): Promise<Role[]> {
    return this.roleRepo.find({
      order: { createdAt: 'ASC' },
    });
  }

  /**
   * Busca un rol por su identificador único.
   * @param id - UUID del rol a buscar
   * @returns El rol encontrado
   * @throws NotFoundException si el rol no existe
   */
  async findOne(id: string): Promise<Role> {
    const role = await this.roleRepo.findOne({ where: { id } });

    if (!role) {
      throw new NotFoundException(`El rol con ID "${id}" no fue encontrado`);
    }

    return role;
  }

  /**
   * Crea un nuevo rol personalizado (no de sistema).
   * Verifica que no exista un rol con el mismo slug antes de crear.
   * @param dto - Datos del rol a crear
   * @returns El rol recién creado
   * @throws ConflictException si ya existe un rol con el mismo slug
   */
  async create(dto: CreateRoleDto): Promise<Role> {
    // Verificar que no exista un rol con el mismo slug
    const existing = await this.roleRepo.findOne({ where: { slug: dto.slug } });

    if (existing) {
      throw new ConflictException(`Ya existe un rol con el slug "${dto.slug}"`);
    }

    const role = this.roleRepo.create({
      ...dto,
      isSystem: false, // Los roles creados manualmente nunca son de sistema
    });

    const saved = await this.roleRepo.save(role);
    this.logger.log(`Rol creado: ${saved.name} (${saved.slug})`);

    return saved;
  }

  /**
   * Actualiza un rol existente.
   * Prohíbe la edición del rol super_admin si es un rol de sistema.
   * @param id - UUID del rol a actualizar
   * @param dto - Campos a modificar
   * @returns El rol actualizado
   * @throws NotFoundException si el rol no existe
   * @throws ForbiddenException si se intenta editar el super_admin de sistema
   * @throws ConflictException si el nuevo slug ya está en uso por otro rol
   */
  async update(id: string, dto: UpdateRoleDto): Promise<Role> {
    const role = await this.findOne(id);

    // Proteger el rol super_admin de sistema contra ediciones
    if (role.isSystem && role.slug === 'super_admin') {
      throw new ForbiddenException(
        'No se puede modificar el rol de super administrador del sistema',
      );
    }

    // Si se intenta cambiar el slug, verificar que no esté en uso
    if (dto.slug && dto.slug !== role.slug) {
      const existing = await this.roleRepo.findOne({ where: { slug: dto.slug } });

      if (existing) {
        throw new ConflictException(`Ya existe un rol con el slug "${dto.slug}"`);
      }
    }

    Object.assign(role, dto);
    const updated = await this.roleRepo.save(role);
    this.logger.log(`Rol actualizado: ${updated.name} (${updated.slug})`);

    return updated;
  }

  /**
   * Elimina un rol del sistema.
   * Solo permite eliminar roles que no sean de sistema (isSystem = false).
   * @param id - UUID del rol a eliminar
   * @throws NotFoundException si el rol no existe
   * @throws ForbiddenException si el rol es de sistema y no puede eliminarse
   */
  async remove(id: string): Promise<void> {
    const role = await this.findOne(id);

    // Los roles de sistema no pueden eliminarse
    if (role.isSystem) {
      throw new ForbiddenException(
        `El rol "${role.name}" es un rol de sistema y no puede ser eliminado`,
      );
    }

    await this.roleRepo.remove(role);
    this.logger.log(`Rol eliminado: ${role.name} (${role.slug})`);
  }
}
