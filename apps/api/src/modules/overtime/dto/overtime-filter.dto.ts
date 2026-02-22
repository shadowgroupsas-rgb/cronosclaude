import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsOptional, IsDateString, IsEnum, IsUUID } from 'class-validator';
import { PaginationDto } from '@/common/dto/pagination.dto';

// Períodos predefinidos para filtrar reportes de horas extras
export enum OvertimeFilterPeriod {
  DAY = 'day',
  WEEK = 'week',
  MONTH = 'month',
}

// DTO para filtrar y paginar registros de horas extras
export class OvertimeFilterDto extends PaginationDto {
  @ApiPropertyOptional({ description: 'Fecha de inicio del rango (ISO 8601)', example: '2026-01-01' })
  @IsOptional()
  @IsDateString({}, { message: 'La fecha de inicio debe ser una fecha válida en formato ISO 8601' })
  from?: string;

  @ApiPropertyOptional({ description: 'Fecha de fin del rango (ISO 8601)', example: '2026-01-31' })
  @IsOptional()
  @IsDateString({}, { message: 'La fecha de fin debe ser una fecha válida en formato ISO 8601' })
  to?: string;

  @ApiPropertyOptional({ description: 'Período predefinido para el filtro', enum: OvertimeFilterPeriod })
  @IsOptional()
  @IsEnum(OvertimeFilterPeriod, { message: 'El período debe ser: day, week o month' })
  period?: OvertimeFilterPeriod;

  @ApiPropertyOptional({ description: 'ID del usuario para filtrar', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890' })
  @IsOptional()
  @IsUUID('4', { message: 'El ID de usuario debe ser un UUID válido' })
  userId?: string;

  @ApiPropertyOptional({ description: 'ID del departamento para filtrar', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890' })
  @IsOptional()
  @IsUUID('4', { message: 'El ID de departamento debe ser un UUID válido' })
  departmentId?: string;
}
