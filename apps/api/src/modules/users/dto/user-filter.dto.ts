import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsOptional, IsUUID, IsBoolean, IsString } from 'class-validator';
import { Transform } from 'class-transformer';
import { PaginationDto } from '@/common/dto/pagination.dto';

// DTO para filtrar y buscar usuarios con paginación
export class UserFilterDto extends PaginationDto {
  @ApiPropertyOptional({
    description: 'Filtrar por ID de rol',
    example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
  })
  @IsOptional()
  @IsUUID('4', { message: 'El ID del rol debe ser un UUID válido' })
  roleId?: string;

  @ApiPropertyOptional({
    description: 'Filtrar por ID de departamento',
    example: 'f1e2d3c4-b5a6-7890-abcd-ef1234567890',
  })
  @IsOptional()
  @IsUUID('4', { message: 'El ID del departamento debe ser un UUID válido' })
  departmentId?: string;

  @ApiPropertyOptional({
    description: 'Filtrar por estado activo/inactivo',
    example: true,
  })
  @IsOptional()
  @IsBoolean({ message: 'El campo isActive debe ser un valor booleano' })
  @Transform(({ value }) => {
    if (value === 'true') return true;
    if (value === 'false') return false;
    return value;
  })
  isActive?: boolean;

  @ApiPropertyOptional({
    description: 'Buscar por nombre, apellido o correo electrónico',
    example: 'Juan',
  })
  @IsOptional()
  @IsString({ message: 'El campo de búsqueda debe ser una cadena de texto' })
  search?: string;
}
