import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsString, IsNotEmpty, IsArray, IsOptional, IsBoolean, Matches, MaxLength, ValidateNested } from 'class-validator';
import { Type } from 'class-transformer';

class ZonePointDto {
  @ApiProperty({ example: 18.4861 })
  lat!: number;

  @ApiProperty({ example: -69.9312 })
  lng!: number;
}

export class CreateZoneDto {
  @ApiProperty({ description: 'Nombre de la zona', example: 'Oficina Central', maxLength: 100 })
  @IsString()
  @IsNotEmpty()
  @MaxLength(100)
  name!: string;

  @ApiPropertyOptional({ description: 'Descripción de la zona' })
  @IsOptional()
  @IsString()
  description?: string;

  @ApiProperty({ description: 'Polígono que define la geocerca', type: [ZonePointDto] })
  @IsArray()
  @ValidateNested({ each: true })
  @Type(() => ZonePointDto)
  polygon!: ZonePointDto[];

  @ApiPropertyOptional({ description: 'Color hexadecimal para la zona en el mapa', example: '#CC2229' })
  @IsOptional()
  @IsString()
  @Matches(/^#[0-9A-Fa-f]{6}$/, { message: 'El color debe ser un código hexadecimal válido (#RRGGBB)' })
  color?: string;

  @ApiPropertyOptional({ description: 'Estado activo de la zona', default: true })
  @IsOptional()
  @IsBoolean()
  isActive?: boolean;
}
