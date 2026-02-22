import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { LocationLog } from '@/database/entities/location-log.entity';
import { OvertimeRecord, OvertimeStatus } from '@/database/entities/overtime-record.entity';

@Injectable()
export class TrackingService {
  private readonly logger = new Logger(TrackingService.name);

  constructor(
    @InjectRepository(LocationLog) private readonly locationLogRepo: Repository<LocationLog>,
    @InjectRepository(OvertimeRecord) private readonly overtimeRepo: Repository<OvertimeRecord>,
  ) {}

  async getLocationHistory(overtimeRecordId: string): Promise<LocationLog[]> {
    return this.locationLogRepo.find({
      where: { overtimeRecordId },
      order: { timestamp: 'ASC' },
    });
  }

  async trackLocation(
    overtimeRecordId: string,
    latitude: number,
    longitude: number,
    accuracy?: number,
  ): Promise<LocationLog> {
    const log = this.locationLogRepo.create({
      overtimeRecordId,
      latitude,
      longitude,
      accuracy: accuracy || null,
      timestamp: new Date(),
    });

    const saved = await this.locationLogRepo.save(log);
    this.logger.debug(`Ubicación registrada: record=${overtimeRecordId}, lat=${latitude}, lng=${longitude}`);
    return saved;
  }

  async getActiveUsersLocations(): Promise<{ userId: string; firstName: string; lastName: string; latitude: number; longitude: number; timestamp: Date; recordId: string }[]> {
    const activeRecords = await this.overtimeRepo.find({
      where: { status: OvertimeStatus.ACTIVE },
      relations: ['user', 'locationLogs'],
    });

    return activeRecords
      .filter((r) => r.locationLogs.length > 0)
      .map((r) => {
        const lastLog = r.locationLogs.sort(
          (a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime(),
        )[0];
        return {
          userId: r.userId,
          firstName: r.user.firstName,
          lastName: r.user.lastName,
          latitude: Number(lastLog.latitude),
          longitude: Number(lastLog.longitude),
          timestamp: lastLog.timestamp,
          recordId: r.id,
        };
      });
  }
}
