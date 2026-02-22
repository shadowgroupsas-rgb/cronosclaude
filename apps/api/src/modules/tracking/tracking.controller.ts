import { Controller, Get, Post, Body, Param, ParseUUIDPipe, UseGuards } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { JwtAuthGuard } from '@/common/guards/jwt-auth.guard';
import { PermissionsGuard } from '@/common/guards/permissions.guard';
import { RequirePermissions } from '@/common/decorators/permissions.decorator';
import { TrackingService } from './tracking.service';

@ApiTags('Rastreo GPS')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, PermissionsGuard)
@Controller('tracking')
export class TrackingController {
  constructor(private readonly trackingService: TrackingService) {}

  @Get('active-users')
  @RequirePermissions('tracking:read')
  @ApiOperation({ summary: 'Obtener ubicaciones de usuarios con horas extra activas' })
  @ApiResponse({ status: 200, description: 'Lista de ubicaciones activas' })
  async getActiveUsersLocations() {
    return this.trackingService.getActiveUsersLocations();
  }

  @Get(':recordId/history')
  @RequirePermissions('tracking:read')
  @ApiOperation({ summary: 'Obtener historial de ubicaciones de un registro' })
  @ApiResponse({ status: 200, description: 'Historial de ubicaciones' })
  async getLocationHistory(@Param('recordId', ParseUUIDPipe) recordId: string) {
    return this.trackingService.getLocationHistory(recordId);
  }

  @Post(':recordId/location')
  @ApiOperation({ summary: 'Registrar nueva ubicación GPS' })
  @ApiResponse({ status: 201, description: 'Ubicación registrada' })
  async trackLocation(
    @Param('recordId', ParseUUIDPipe) recordId: string,
    @Body() body: { latitude: number; longitude: number; accuracy?: number },
  ) {
    return this.trackingService.trackLocation(recordId, body.latitude, body.longitude, body.accuracy);
  }
}
