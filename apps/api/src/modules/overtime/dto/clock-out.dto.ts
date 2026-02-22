import { ApiProperty } from '@nestjs/swagger';
import { IsNumber, IsString, MinLength } from 'class-validator';

// DTO para registrar salida de hora extra
export class ClockOutDto {
  @ApiProperty({ description: 'Latitud de la ubicación al marcar salida', example: 18.4861 })
  @IsNumber({}, { message: 'La latitud debe ser un número válido' })
  latitude!: number;

  @ApiProperty({ description: 'Longitud de la ubicación al marcar salida', example: -69.9312 })
  @IsNumber({}, { message: 'La longitud debe ser un número válido' })
  longitude!: number;

  @ApiProperty({ description: 'Descripción de las actividades realizadas durante la hora extra', example: 'Cierre de inventario mensual' })
  @IsString({ message: 'La descripción debe ser un texto' })
  @MinLength(5, { message: 'La descripción debe tener al menos 5 caracteres' })
  description!: string;
}
