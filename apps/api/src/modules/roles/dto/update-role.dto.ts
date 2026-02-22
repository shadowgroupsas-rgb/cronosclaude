import { PartialType } from '@nestjs/swagger';
import { CreateRoleDto } from './create-role.dto';

/**
 * DTO para la actualización parcial de un rol.
 * Hereda todas las validaciones de CreateRoleDto pero todos los campos son opcionales.
 */
export class UpdateRoleDto extends PartialType(CreateRoleDto) {}
