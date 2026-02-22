import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsString, IsNotEmpty, Matches, MaxLength, IsOptional, IsObject } from 'class-validator';
import { RolePermissions } from '@/database/entities/role.entity';

/**
 * DTO para la creación de un nuevo rol.
 * Valida los campos requeridos y opcionales antes de persistir.
 */
export class CreateRoleDto {
  @ApiProperty({
    description: 'Nombre visible del rol',
    example: 'Supervisor de Turno',
    maxLength: 100,
  })
  @IsString({ message: 'El nombre debe ser una cadena de texto' })
  @IsNotEmpty({ message: 'El nombre del rol es obligatorio' })
  @MaxLength(100, { message: 'El nombre no puede exceder 100 caracteres' })
  name!: string;

  @ApiProperty({
    description: 'Slug único del rol (solo letras minúsculas y guiones bajos)',
    example: 'supervisor_turno',
    maxLength: 50,
  })
  @IsString({ message: 'El slug debe ser una cadena de texto' })
  @IsNotEmpty({ message: 'El slug del rol es obligatorio' })
  @MaxLength(50, { message: 'El slug no puede exceder 50 caracteres' })
  @Matches(/^[a-z_]+$/, {
    message: 'El slug solo puede contener letras minúsculas y guiones bajos',
  })
  slug!: string;

  @ApiPropertyOptional({
    description: 'Descripción del rol y sus responsabilidades',
    example: 'Encargado de supervisar los turnos del personal operativo',
  })
  @IsOptional()
  @IsString({ message: 'La descripción debe ser una cadena de texto' })
  description?: string;

  @ApiPropertyOptional({
    description: 'Mapa de permisos por recurso. Cada clave es un recurso y el valor es un arreglo de acciones permitidas',
    example: { roles: ['read'], users: ['read', 'update'], overtime: ['read', 'create'] },
  })
  @IsOptional()
  @IsObject({ message: 'Los permisos deben ser un objeto válido' })
  permissions?: RolePermissions;
}
