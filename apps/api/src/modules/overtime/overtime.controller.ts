import {
  Controller,
  Get,
  Post,
  Body,
  Param,
  Query,
  ParseUUIDPipe,
  UseGuards,
} from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth } from '@nestjs/swagger';
import { JwtAuthGuard } from '@/common/guards/jwt-auth.guard';
import { PermissionsGuard } from '@/common/guards/permissions.guard';
import { RequirePermissions } from '@/common/decorators/permissions.decorator';
import { CurrentUser } from '@/common/decorators/current-user.decorator';
import { User } from '@/database/entities/user.entity';
import { OvertimeService } from './overtime.service';
import { ClockInDto } from './dto/clock-in.dto';
import { ClockOutDto } from './dto/clock-out.dto';
import { OvertimeFilterDto } from './dto/overtime-filter.dto';

@ApiTags('Horas Extra')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, PermissionsGuard)
@Controller('overtime')
export class OvertimeController {
  constructor(private readonly overtimeService: OvertimeService) {}

  @Post('clock-in')
  @RequirePermissions('overtime:create')
  @ApiOperation({ summary: 'Registrar entrada de hora extra (clock-in)' })
  @ApiResponse({ status: 201, description: 'Clock-in registrado' })
  @ApiResponse({ status: 400, description: 'Ya existe un registro activo' })
  async clockIn(@CurrentUser() user: User, @Body() dto: ClockInDto) {
    return this.overtimeService.clockIn(user.id, dto);
  }

  @Post(':id/clock-out')
  @RequirePermissions('overtime:create')
  @ApiOperation({ summary: 'Registrar salida de hora extra (clock-out)' })
  @ApiResponse({ status: 200, description: 'Clock-out registrado' })
  @ApiResponse({ status: 404, description: 'Registro activo no encontrado' })
  async clockOut(
    @CurrentUser() user: User,
    @Param('id', ParseUUIDPipe) id: string,
    @Body() dto: ClockOutDto,
  ) {
    return this.overtimeService.clockOut(user.id, id, dto);
  }

  @Get()
  @RequirePermissions('overtime:read')
  @ApiOperation({ summary: 'Listar registros de horas extra con filtros' })
  @ApiResponse({ status: 200, description: 'Lista paginada de registros' })
  async findAll(@Query() filter: OvertimeFilterDto) {
    return this.overtimeService.findAll(filter);
  }

  @Get('active')
  @ApiOperation({ summary: 'Obtener registro activo del usuario actual' })
  @ApiResponse({ status: 200, description: 'Registro activo o null' })
  async getActiveRecord(@CurrentUser() user: User) {
    const record = await this.overtimeService.getActiveRecord(user.id);
    return { data: record };
  }

  @Get(':id')
  @RequirePermissions('overtime:read')
  @ApiOperation({ summary: 'Obtener detalle de registro de hora extra' })
  @ApiResponse({ status: 200, description: 'Registro encontrado' })
  @ApiResponse({ status: 404, description: 'Registro no encontrado' })
  async findOne(@Param('id', ParseUUIDPipe) id: string) {
    return this.overtimeService.findOne(id);
  }

  @Post(':id/cancel')
  @RequirePermissions('overtime:create')
  @ApiOperation({ summary: 'Cancelar registro activo de hora extra' })
  @ApiResponse({ status: 200, description: 'Registro cancelado' })
  async cancel(@CurrentUser() user: User, @Param('id', ParseUUIDPipe) id: string) {
    return this.overtimeService.cancelRecord(user.id, id);
  }

  @Post(':id/location')
  @ApiOperation({ summary: 'Registrar ubicación durante hora extra activa' })
  @ApiResponse({ status: 201, description: 'Ubicación registrada' })
  async addLocation(
    @Param('id', ParseUUIDPipe) id: string,
    @Body() dto: ClockInDto,
  ) {
    return this.overtimeService.addLocationLog(id, dto.latitude, dto.longitude);
  }
}
