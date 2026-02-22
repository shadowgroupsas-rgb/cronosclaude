import {
  Injectable,
  NotFoundException,
  BadRequestException,
  Logger,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { OvertimeRecord, OvertimeStatus } from '@/database/entities/overtime-record.entity';
import { Zone } from '@/database/entities/zone.entity';
import { LocationLog } from '@/database/entities/location-log.entity';
import { User } from '@/database/entities/user.entity';
import { ClockInDto } from './dto/clock-in.dto';
import { ClockOutDto } from './dto/clock-out.dto';
import { OvertimeFilterDto } from './dto/overtime-filter.dto';
import { PaginatedResponseDto } from '@/common/dto/response.dto';
import { isNocturnalOvertime, calculateTotalMinutes, getDayOfWeek } from '@/shared/overtime-utils/overtime-utils';
import { isPointInPolygon } from '@/shared/geocerca/geocerca.utils';

@Injectable()
export class OvertimeService {
  private readonly logger = new Logger(OvertimeService.name);

  constructor(
    @InjectRepository(OvertimeRecord) private readonly overtimeRepo: Repository<OvertimeRecord>,
    @InjectRepository(Zone) private readonly zoneRepo: Repository<Zone>,
    @InjectRepository(LocationLog) private readonly locationLogRepo: Repository<LocationLog>,
  ) {}

  async clockIn(userId: string, dto: ClockInDto): Promise<OvertimeRecord> {
    // Verificar que no haya un registro activo
    const activeRecord = await this.overtimeRepo.findOne({
      where: { userId, status: OvertimeStatus.ACTIVE },
    });

    if (activeRecord) {
      throw new BadRequestException('Ya tienes un registro de hora extra activo. Debes marcar salida primero.');
    }

    const now = new Date();

    // Detectar zona por coordenadas
    const zone = await this.detectZone(dto.latitude, dto.longitude);

    const record = this.overtimeRepo.create({
      userId,
      clockIn: now,
      clockInLat: dto.latitude,
      clockInLng: dto.longitude,
      dayOfWeek: getDayOfWeek(now),
      status: OvertimeStatus.ACTIVE,
      zoneId: zone?.id || null,
    });

    const saved = await this.overtimeRepo.save(record);

    // Registrar primera ubicación
    await this.locationLogRepo.save(
      this.locationLogRepo.create({
        overtimeRecordId: saved.id,
        latitude: dto.latitude,
        longitude: dto.longitude,
        timestamp: now,
      }),
    );

    this.logger.log(`Clock-in registrado para usuario ${userId} (Record: ${saved.id})`);
    return this.findOne(saved.id);
  }

  async clockOut(userId: string, recordId: string, dto: ClockOutDto): Promise<OvertimeRecord> {
    const record = await this.overtimeRepo.findOne({
      where: { id: recordId, userId, status: OvertimeStatus.ACTIVE },
    });

    if (!record) {
      throw new NotFoundException('No se encontró un registro activo de hora extra');
    }

    const now = new Date();
    const totalMinutes = calculateTotalMinutes(record.clockIn, now);

    if (totalMinutes < 1) {
      throw new BadRequestException('El tiempo mínimo de hora extra es 1 minuto');
    }

    record.clockOut = now;
    record.clockOutLat = dto.latitude;
    record.clockOutLng = dto.longitude;
    record.description = dto.description;
    record.totalMinutes = totalMinutes;
    record.isNocturnal = isNocturnalOvertime(record.clockIn, now);
    record.status = OvertimeStatus.COMPLETED;

    await this.overtimeRepo.save(record);

    // Registrar ubicación de salida
    await this.locationLogRepo.save(
      this.locationLogRepo.create({
        overtimeRecordId: record.id,
        latitude: dto.latitude,
        longitude: dto.longitude,
        timestamp: now,
      }),
    );

    this.logger.log(`Clock-out registrado: ${record.id} (${totalMinutes} min, nocturno: ${record.isNocturnal})`);
    return this.findOne(record.id);
  }

  async findAll(filter: OvertimeFilterDto): Promise<PaginatedResponseDto<OvertimeRecord>> {
    const { page = 1, limit = 20, from, to, userId, departmentId } = filter;

    const qb = this.overtimeRepo
      .createQueryBuilder('ot')
      .leftJoinAndSelect('ot.user', 'user')
      .leftJoinAndSelect('ot.zone', 'zone');

    if (userId) {
      qb.andWhere('ot.userId = :userId', { userId });
    }
    if (departmentId) {
      qb.andWhere('user.departmentId = :departmentId', { departmentId });
    }
    if (from) {
      qb.andWhere('ot.clockIn >= :from', { from });
    }
    if (to) {
      qb.andWhere('ot.clockIn <= :to', { to });
    }

    qb.orderBy('ot.clockIn', 'DESC');
    qb.skip(filter.skip).take(limit);

    const [records, total] = await qb.getManyAndCount();
    return PaginatedResponseDto.paginated<OvertimeRecord>(records, total, page, limit);
  }

  async findOne(id: string): Promise<OvertimeRecord> {
    const record = await this.overtimeRepo.findOne({
      where: { id },
      relations: ['user', 'zone', 'locationLogs'],
    });

    if (!record) {
      throw new NotFoundException(`Registro de hora extra con ID ${id} no encontrado`);
    }

    return record;
  }

  async getActiveRecord(userId: string): Promise<OvertimeRecord | null> {
    return this.overtimeRepo.findOne({
      where: { userId, status: OvertimeStatus.ACTIVE },
      relations: ['zone'],
    });
  }

  async cancelRecord(userId: string, recordId: string): Promise<OvertimeRecord> {
    const record = await this.overtimeRepo.findOne({
      where: { id: recordId, userId, status: OvertimeStatus.ACTIVE },
    });

    if (!record) {
      throw new NotFoundException('No se encontró un registro activo para cancelar');
    }

    record.status = OvertimeStatus.CANCELLED;
    await this.overtimeRepo.save(record);
    this.logger.log(`Registro cancelado: ${record.id}`);
    return record;
  }

  async addLocationLog(recordId: string, latitude: number, longitude: number): Promise<LocationLog> {
    const record = await this.overtimeRepo.findOne({
      where: { id: recordId, status: OvertimeStatus.ACTIVE },
    });

    if (!record) {
      throw new NotFoundException('Registro activo no encontrado');
    }

    const log = this.locationLogRepo.create({
      overtimeRecordId: recordId,
      latitude,
      longitude,
      timestamp: new Date(),
    });

    return this.locationLogRepo.save(log);
  }

  private async detectZone(lat: number, lng: number): Promise<Zone | null> {
    const zones = await this.zoneRepo.find({ where: { isActive: true } });

    for (const zone of zones) {
      if (isPointInPolygon({ lat, lng }, zone.polygon)) {
        return zone;
      }
    }

    return null;
  }
}
