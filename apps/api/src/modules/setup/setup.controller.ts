import { Controller, Get, Put, Delete, Body, Param, UseGuards } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { JwtAuthGuard } from '@/common/guards/jwt-auth.guard';
import { RolesGuard } from '@/common/guards/roles.guard';
import { Roles } from '@/common/decorators/roles.decorator';
import { SetupService } from './setup.service';

@ApiTags('Configuración del sistema')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Roles('super_admin')
@Controller('setup')
export class SetupController {
  constructor(private readonly setupService: SetupService) {}

  @Get()
  @ApiOperation({ summary: 'Obtener todas las configuraciones del sistema' })
  @ApiResponse({ status: 200, description: 'Lista de configuraciones' })
  async getAllConfigs() {
    return this.setupService.getAllConfigs();
  }

  @Get(':key')
  @ApiOperation({ summary: 'Obtener una configuración por clave' })
  @ApiResponse({ status: 200, description: 'Valor de la configuración' })
  async getConfig(@Param('key') key: string) {
    const value = await this.setupService.getConfig(key);
    return { key, value };
  }

  @Put(':key')
  @ApiOperation({ summary: 'Crear o actualizar una configuración' })
  @ApiResponse({ status: 200, description: 'Configuración actualizada' })
  async setConfig(@Param('key') key: string, @Body() body: { value: unknown }) {
    return this.setupService.setConfig(key, body.value);
  }

  @Delete(':key')
  @ApiOperation({ summary: 'Eliminar una configuración' })
  @ApiResponse({ status: 200, description: 'Configuración eliminada' })
  async deleteConfig(@Param('key') key: string) {
    await this.setupService.deleteConfig(key);
    return { message: `Configuración "${key}" eliminada` };
  }
}
