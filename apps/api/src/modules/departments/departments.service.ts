import {
  Injectable,
  NotFoundException,
  ConflictException,
  Logger,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Department } from '@/database/entities/department.entity';
import { User } from '@/database/entities/user.entity';
import { CreateDepartmentDto } from './dto/create-department.dto';
import { UpdateDepartmentDto } from './dto/update-department.dto';

@Injectable()
export class DepartmentsService {
  private readonly logger = new Logger(DepartmentsService.name);

  constructor(
    @InjectRepository(Department) private readonly deptRepo: Repository<Department>,
    @InjectRepository(User) private readonly userRepo: Repository<User>,
  ) {}

  async findAll(): Promise<Department[]> {
    return this.deptRepo.find({
      relations: ['manager', 'employees'],
      order: { name: 'ASC' },
    });
  }

  async findOne(id: string): Promise<Department> {
    const dept = await this.deptRepo.findOne({
      where: { id },
      relations: ['manager', 'employees'],
    });

    if (!dept) {
      throw new NotFoundException(`Departamento con ID ${id} no encontrado`);
    }

    return dept;
  }

  async create(dto: CreateDepartmentDto): Promise<Department> {
    const existing = await this.deptRepo.findOne({ where: { name: dto.name } });
    if (existing) {
      throw new ConflictException(`Ya existe un departamento con el nombre "${dto.name}"`);
    }

    const dept = this.deptRepo.create(dto);
    const saved = await this.deptRepo.save(dept);
    this.logger.log(`Departamento creado: ${saved.name} (ID: ${saved.id})`);
    return this.findOne(saved.id);
  }

  async update(id: string, dto: UpdateDepartmentDto): Promise<Department> {
    const dept = await this.findOne(id);

    if (dto.name && dto.name !== dept.name) {
      const existing = await this.deptRepo.findOne({ where: { name: dto.name } });
      if (existing) {
        throw new ConflictException(`Ya existe un departamento con el nombre "${dto.name}"`);
      }
    }

    Object.assign(dept, dto);
    await this.deptRepo.save(dept);
    this.logger.log(`Departamento actualizado: ${dept.name} (ID: ${id})`);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    const dept = await this.findOne(id);
    await this.deptRepo.remove(dept);
    this.logger.log(`Departamento eliminado: ${dept.name}`);
  }

  async assignEmployee(departmentId: string, userId: string): Promise<User> {
    await this.findOne(departmentId);
    const user = await this.userRepo.findOne({ where: { id: userId } });

    if (!user) {
      throw new NotFoundException(`Usuario con ID ${userId} no encontrado`);
    }

    user.departmentId = departmentId;
    await this.userRepo.save(user);
    this.logger.log(`Usuario ${userId} asignado al departamento ${departmentId}`);
    return user;
  }
}
