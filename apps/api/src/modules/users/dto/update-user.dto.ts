import { ApiPropertyOptional } from '@nestjs/swagger';
import { PartialType } from '@nestjs/swagger';
import { IsBoolean, IsOptional } from 'class-validator';
import { CreateUserDto } from './create-user.dto';

// DTO para la actualización de un usuario existente
// Hereda todos los campos de CreateUserDto como opcionales y agrega isActive
export class UpdateUserDto extends PartialType(CreateUserDto) {
  @ApiPropertyOptional({
    description: 'Indica si el usuario está activo en el sistema',
    example: true,
  })
  @IsOptional()
  @IsBoolean({ message: 'El campo isActive debe ser un valor booleano' })
  isActive?: boolean;
}
