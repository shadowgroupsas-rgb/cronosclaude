import { ApiProperty } from '@nestjs/swagger';
import { IsUUID, IsNotEmpty } from 'class-validator';

/**
 * DTO para asignar un empleado a un departamento.
 * Requiere el ID del usuario que sera asignado.
 */
export class AssignEmployeeDto {
  @ApiProperty({
    description: 'ID del usuario que sera asignado al departamento',
    example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
  })
  @IsUUID('4', { message: 'El userId debe ser un UUID valido' })
  @IsNotEmpty({ message: 'El userId es obligatorio' })
  userId!: string;
}
