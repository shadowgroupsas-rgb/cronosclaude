import {
  Controller,
  Get,
  Post,
  Put,
  Delete,
  Body,
  Param,
  ParseUUIDPipe,
  UseGuards,
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { JwtAuthGuard } from '@/common/guards/jwt-auth.guard';
import { PermissionsGuard } from '@/common/guards/permissions.guard';
import { RequirePermissions } from '@/common/decorators/permissions.decorator';
import { ZonesService } from './zones.service';
import { CreateZoneDto } from './dto/create-zone.dto';
import { UpdateZoneDto } from './dto/update-zone.dto';

@ApiTags('Zonas / Geocercas')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, PermissionsGuard)
@Controller('zones')
export class ZonesController {
  constructor(private readonly zonesService: ZonesService) {}

  @Get()
  @RequirePermissions('zones:read')
  @ApiOperation({ summary: 'Listar todas las zonas/geocercas' })
  @ApiResponse({ status: 200, description: 'Lista de zonas' })
  async findAll() {
    return this.zonesService.findAll();
  }

  @Get(':id')
  @RequirePermissions('zones:read')
  @ApiOperation({ summary: 'Obtener zona por ID' })
  @ApiResponse({ status: 200, description: 'Zona encontrada' })
  async findOne(@Param('id', ParseUUIDPipe) id: string) {
    return this.zonesService.findOne(id);
  }

  @Post()
  @RequirePermissions('zones:create')
  @ApiOperation({ summary: 'Crear nueva zona/geocerca' })
  @ApiResponse({ status: 201, description: 'Zona creada' })
  async create(@Body() dto: CreateZoneDto) {
    return this.zonesService.create(dto);
  }

  @Put(':id')
  @RequirePermissions('zones:update')
  @ApiOperation({ summary: 'Actualizar zona existente' })
  @ApiResponse({ status: 200, description: 'Zona actualizada' })
  async update(@Param('id', ParseUUIDPipe) id: string, @Body() dto: UpdateZoneDto) {
    return this.zonesService.update(id, dto);
  }

  @Delete(':id')
  @RequirePermissions('zones:delete')
  @ApiOperation({ summary: 'Eliminar zona' })
  @ApiResponse({ status: 200, description: 'Zona eliminada' })
  async remove(@Param('id', ParseUUIDPipe) id: string) {
    await this.zonesService.remove(id);
    return { message: 'Zona eliminada exitosamente' };
  }
}
