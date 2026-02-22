import { Controller, Get, Query, Res, UseGuards } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiBearerAuth, ApiQuery } from '@nestjs/swagger';
import { Response } from 'express';
import { JwtAuthGuard } from '@/common/guards/jwt-auth.guard';
import { PermissionsGuard } from '@/common/guards/permissions.guard';
import { RequirePermissions } from '@/common/decorators/permissions.decorator';
import { ReportsService, OvertimeReportFilter } from './reports.service';

@ApiTags('Reportes')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard, PermissionsGuard)
@Controller('reports')
export class ReportsController {
  constructor(private readonly reportsService: ReportsService) {}

  @Get('overtime')
  @RequirePermissions('reports:read')
  @ApiOperation({ summary: 'Obtener reporte consolidado de horas extras' })
  @ApiResponse({ status: 200, description: 'Reporte consolidado' })
  @ApiQuery({ name: 'userId', required: false })
  @ApiQuery({ name: 'departmentId', required: false })
  @ApiQuery({ name: 'from', required: false, description: 'Fecha inicio (ISO)' })
  @ApiQuery({ name: 'to', required: false, description: 'Fecha fin (ISO)' })
  async getOvertimeReport(
    @Query('userId') userId?: string,
    @Query('departmentId') departmentId?: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    const filter: OvertimeReportFilter = { userId, departmentId, from, to };
    const data = await this.reportsService.getOvertimeReport(filter);
    return { success: true, data };
  }

  @Get('export/pdf')
  @RequirePermissions('reports:export')
  @ApiOperation({ summary: 'Exportar reporte de horas extras en PDF' })
  @ApiResponse({ status: 200, description: 'Archivo PDF generado' })
  @ApiQuery({ name: 'userId', required: false })
  @ApiQuery({ name: 'departmentId', required: false })
  @ApiQuery({ name: 'from', required: false })
  @ApiQuery({ name: 'to', required: false })
  async exportPdf(
    @Res() res: Response,
    @Query('userId') userId?: string,
    @Query('departmentId') departmentId?: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    const filter: OvertimeReportFilter = { userId, departmentId, from, to };
    const buffer = await this.reportsService.generatePdf(filter);

    res.set({
      'Content-Type': 'application/pdf',
      'Content-Disposition': `attachment; filename="reporte-horas-extras-${Date.now()}.pdf"`,
      'Content-Length': buffer.length,
    });
    res.end(buffer);
  }

  @Get('export/excel')
  @RequirePermissions('reports:export')
  @ApiOperation({ summary: 'Exportar reporte de horas extras en Excel' })
  @ApiResponse({ status: 200, description: 'Archivo Excel generado' })
  @ApiQuery({ name: 'userId', required: false })
  @ApiQuery({ name: 'departmentId', required: false })
  @ApiQuery({ name: 'from', required: false })
  @ApiQuery({ name: 'to', required: false })
  async exportExcel(
    @Res() res: Response,
    @Query('userId') userId?: string,
    @Query('departmentId') departmentId?: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    const filter: OvertimeReportFilter = { userId, departmentId, from, to };
    const buffer = await this.reportsService.generateExcel(filter);

    res.set({
      'Content-Type': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition': `attachment; filename="reporte-horas-extras-${Date.now()}.xlsx"`,
      'Content-Length': buffer.length,
    });
    res.end(buffer);
  }
}
