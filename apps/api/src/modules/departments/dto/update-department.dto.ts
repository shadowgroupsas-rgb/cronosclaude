import { PartialType } from '@nestjs/swagger';
import { CreateDepartmentDto } from './create-department.dto';

/**
 * DTO para la actualizacion de un departamento.
 * Todos los campos son opcionales (hereda de CreateDepartmentDto con PartialType).
 */
export class UpdateDepartmentDto extends PartialType(CreateDepartmentDto) {}
