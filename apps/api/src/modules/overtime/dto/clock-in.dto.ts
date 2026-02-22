import { ApiProperty } from '@nestjs/swagger';
import { IsNumber } from 'class-validator';

// DTO para registrar entrada de hora extra
export class ClockInDto {
  @ApiProperty({ description: 'Latitud de la ubicación al marcar entrada', example: 18.4861 })
  @IsNumber({}, { message: 'La latitud debe ser un número válido' })
  latitude!: number;

  @ApiProperty({ description: 'Longitud de la ubicación al marcar entrada', example: -69.9312 })
  @IsNumber({}, { message: 'La longitud debe ser un número válido' })
  longitude!: number;
}
