import { Controller, Get, UseGuards } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { JwtAuthGuard } from '@/common/guards/jwt-auth.guard';
import { RolesGuard } from '@/common/guards/roles.guard';
import { Roles } from '@/common/decorators/roles.decorator';
import { GodEyeService } from './god-eye.service';

@ApiTags('God Eye - Vista en tiempo real')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, RolesGuard)
@Controller('god-eye')
export class GodEyeController {
  constructor(private readonly godEyeService: GodEyeService) {}

  @Get('dashboard')
  @Roles('super_admin', 'admin', 'supervisor')
  @ApiOperation({ summary: 'Obtener dashboard en tiempo real con mapa de empleados activos' })
  @ApiResponse({ status: 200, description: 'Dashboard con datos en tiempo real' })
  async getDashboard() {
    return this.godEyeService.getDashboard();
  }
}
