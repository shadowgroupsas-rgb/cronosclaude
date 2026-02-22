import {
  Controller,
  Get,
  Post,
  Put,
  Delete,
  Body,
  Param,
  ParseUUIDPipe,
  UseGuards,
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { JwtAuthGuard } from '@/common/guards/jwt-auth.guard';
import { PermissionsGuard } from '@/common/guards/permissions.guard';
import { RequirePermissions } from '@/common/decorators/permissions.decorator';
import { DepartmentsService } from './departments.service';
import { CreateDepartmentDto } from './dto/create-department.dto';
import { UpdateDepartmentDto } from './dto/update-department.dto';
import { AssignEmployeeDto } from './dto/assign-employee.dto';

@ApiTags('Departamentos')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, PermissionsGuard)
@Controller('departments')
export class DepartmentsController {
  constructor(private readonly departmentsService: DepartmentsService) {}

  @Get()
  @RequirePermissions('departments:read')
  @ApiOperation({ summary: 'Listar todos los departamentos' })
  @ApiResponse({ status: 200, description: 'Lista de departamentos' })
  async findAll() {
    return this.departmentsService.findAll();
  }

  @Get(':id')
  @RequirePermissions('departments:read')
  @ApiOperation({ summary: 'Obtener departamento por ID' })
  @ApiResponse({ status: 200, description: 'Departamento encontrado' })
  @ApiResponse({ status: 404, description: 'Departamento no encontrado' })
  async findOne(@Param('id', ParseUUIDPipe) id: string) {
    return this.departmentsService.findOne(id);
  }

  @Post()
  @RequirePermissions('departments:create')
  @ApiOperation({ summary: 'Crear nuevo departamento' })
  @ApiResponse({ status: 201, description: 'Departamento creado' })
  @ApiResponse({ status: 409, description: 'Nombre ya existe' })
  async create(@Body() dto: CreateDepartmentDto) {
    return this.departmentsService.create(dto);
  }

  @Put(':id')
  @RequirePermissions('departments:update')
  @ApiOperation({ summary: 'Actualizar departamento' })
  @ApiResponse({ status: 200, description: 'Departamento actualizado' })
  async update(@Param('id', ParseUUIDPipe) id: string, @Body() dto: UpdateDepartmentDto) {
    return this.departmentsService.update(id, dto);
  }

  @Delete(':id')
  @RequirePermissions('departments:delete')
  @ApiOperation({ summary: 'Eliminar departamento' })
  @ApiResponse({ status: 200, description: 'Departamento eliminado' })
  async remove(@Param('id', ParseUUIDPipe) id: string) {
    await this.departmentsService.remove(id);
    return { message: 'Departamento eliminado exitosamente' };
  }

  @Post(':id/assign')
  @RequirePermissions('departments:update')
  @ApiOperation({ summary: 'Asignar empleado al departamento' })
  @ApiResponse({ status: 200, description: 'Empleado asignado' })
  async assignEmployee(
    @Param('id', ParseUUIDPipe) id: string,
    @Body() dto: AssignEmployeeDto,
  ) {
    return this.departmentsService.assignEmployee(id, dto.userId);
  }
}
