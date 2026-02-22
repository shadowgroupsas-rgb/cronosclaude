import { Controller, Get, Query, UseGuards } from '@nestjs/common';
import { ApiTags, ApiOperation, ApiResponse, ApiSecurity, ApiQuery } from '@nestjs/swagger';
import { ApiKeyGuard } from '@/common/guards/api-key.guard';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { OvertimeRecord, OvertimeStatus } from '@/database/entities/overtime-record.entity';

@ApiTags('API Externa (Cataleya)')
@ApiSecurity('api-key')
@UseGuards(ApiKeyGuard)
@Controller('external')
export class ExternalController {
  constructor(
    @InjectRepository(OvertimeRecord) private readonly overtimeRepo: Repository<OvertimeRecord>,
  ) {}

  @Get('overtime-summary')
  @ApiOperation({ summary: 'Resumen de horas extras para integración con Cataleya' })
  @ApiResponse({ status: 200, description: 'Resumen consolidado' })
  @ApiQuery({ name: 'from', required: false })
  @ApiQuery({ name: 'to', required: false })
  async getOvertimeSummary(@Query('from') from?: string, @Query('to') to?: string) {
    const qb = this.overtimeRepo
      .createQueryBuilder('ot')
      .innerJoin('ot.user', 'user')
      .select([
        'user.id AS "userId"',
        'user.first_name AS "firstName"',
        'user.last_name AS "lastName"',
        'user.email AS "email"',
        'COUNT(ot.id)::int AS "totalRecords"',
        'COALESCE(SUM(ot.total_minutes), 0)::int AS "totalMinutes"',
      ])
      .where('ot.status = :status', { status: OvertimeStatus.COMPLETED });

    if (from) qb.andWhere('ot.clock_in >= :from', { from });
    if (to) qb.andWhere('ot.clock_in <= :to', { to });

    qb.groupBy('user.id, user.first_name, user.last_name, user.email');
    qb.orderBy('"totalMinutes"', 'DESC');

    const data = await qb.getRawMany();
    return { success: true, data, generatedAt: new Date().toISOString() };
  }

  @Get('active-overtime')
  @ApiOperation({ summary: 'Obtener horas extras activas en tiempo real' })
  @ApiResponse({ status: 200, description: 'Lista de registros activos' })
  async getActiveOvertime() {
    const records = await this.overtimeRepo.find({
      where: { status: OvertimeStatus.ACTIVE },
      relations: ['user', 'zone'],
    });

    return {
      success: true,
      count: records.length,
      data: records.map((r) => ({
        recordId: r.id,
        userId: r.userId,
        userName: `${r.user.firstName} ${r.user.lastName}`,
        clockIn: r.clockIn,
        zone: r.zone?.name || null,
        lat: Number(r.clockInLat),
        lng: Number(r.clockInLng),
      })),
    };
  }
}
