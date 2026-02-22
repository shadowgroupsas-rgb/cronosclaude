import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';

export class ResponseDto<T = unknown> {
  @ApiProperty({ description: 'Indica si la operación fue exitosa' })
  success!: boolean;

  @ApiPropertyOptional({ description: 'Datos de la respuesta' })
  data?: T;

  @ApiPropertyOptional({ description: 'Mensaje descriptivo' })
  message?: string;

  @ApiPropertyOptional({ description: 'Detalle del error' })
  error?: string;

  static ok<T>(data: T, message?: string): ResponseDto<T> {
    const response = new ResponseDto<T>();
    response.success = true;
    response.data = data;
    response.message = message;
    return response;
  }

  static fail(error: string, message?: string): ResponseDto {
    const response = new ResponseDto();
    response.success = false;
    response.error = error;
    response.message = message;
    return response;
  }
}

export class PaginatedResponseDto<T = unknown> extends ResponseDto<T[]> {
  @ApiProperty()
  total!: number;

  @ApiProperty()
  page!: number;

  @ApiProperty()
  limit!: number;

  @ApiProperty()
  totalPages!: number;

  static paginated<T>(data: T[], total: number, page: number, limit: number): PaginatedResponseDto<T> {
    const response = new PaginatedResponseDto<T>();
    response.success = true;
    response.data = data;
    response.total = total;
    response.page = page;
    response.limit = limit;
    response.totalPages = Math.ceil(total / limit);
    return response;
  }
}
