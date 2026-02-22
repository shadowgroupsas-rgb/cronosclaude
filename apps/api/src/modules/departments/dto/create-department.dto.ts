import { ApiProperty } from '@nestjs/swagger';
import { IsString, IsNotEmpty, IsOptional, IsUUID, MaxLength } from 'class-validator';

/**
 * DTO para la creacion de un departamento.
 * Contiene los campos necesarios para registrar un nuevo departamento en el sistema.
 */
export class CreateDepartmentDto {
  @ApiProperty({
    description: 'Nombre del departamento',
    example: 'Recursos Humanos',
    maxLength: 100,
  })
  @IsString({ message: 'El nombre debe ser una cadena de texto' })
  @IsNotEmpty({ message: 'El nombre del departamento es obligatorio' })
  @MaxLength(100, { message: 'El nombre no puede exceder los 100 caracteres' })
  name!: string;

  @ApiProperty({
    description: 'Descripcion del departamento',
    example: 'Departamento encargado de la gestion del personal',
    required: false,
  })
  @IsString({ message: 'La descripcion debe ser una cadena de texto' })
  @IsOptional()
  description?: string;

  @ApiProperty({
    description: 'ID del usuario que sera el gerente del departamento',
    example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
    required: false,
  })
  @IsUUID('4', { message: 'El managerId debe ser un UUID valido' })
  @IsOptional()
  managerId?: string;
}
