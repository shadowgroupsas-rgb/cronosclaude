import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { OvertimeRecord, OvertimeStatus } from '@/database/entities/overtime-record.entity';
import { User } from '@/database/entities/user.entity';
import { Zone } from '@/database/entities/zone.entity';

export interface GodEyeDashboard {
  activeOvertimeCount: number;
  totalEmployees: number;
  activeEmployees: { userId: string; name: string; department: string; clockIn: Date; zone: string | null; lat: number; lng: number }[];
  zones: { id: string; name: string; color: string; polygon: { lat: number; lng: number }[]; activeCount: number }[];
}

@Injectable()
export class GodEyeService {
  private readonly logger = new Logger(GodEyeService.name);

  constructor(
    @InjectRepository(OvertimeRecord) private readonly overtimeRepo: Repository<OvertimeRecord>,
    @InjectRepository(User) private readonly userRepo: Repository<User>,
    @InjectRepository(Zone) private readonly zoneRepo: Repository<Zone>,
  ) {}

  async getDashboard(): Promise<GodEyeDashboard> {
    const activeRecords = await this.overtimeRepo.find({
      where: { status: OvertimeStatus.ACTIVE },
      relations: ['user', 'user.department', 'zone', 'locationLogs'],
    });

    const totalEmployees = await this.userRepo.count({ where: { isActive: true } });
    const zones = await this.zoneRepo.find({ where: { isActive: true } });

    const activeEmployees = activeRecords.map((r) => {
      const lastLog = r.locationLogs?.sort(
        (a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime(),
      )[0];

      return {
        userId: r.userId,
        name: `${r.user.firstName} ${r.user.lastName}`,
        department: r.user.department?.name || 'Sin departamento',
        clockIn: r.clockIn,
        zone: r.zone?.name || null,
        lat: lastLog ? Number(lastLog.latitude) : Number(r.clockInLat),
        lng: lastLog ? Number(lastLog.longitude) : Number(r.clockInLng),
      };
    });

    const zonesWithCounts = zones.map((z) => ({
      id: z.id,
      name: z.name,
      color: z.color,
      polygon: z.polygon,
      activeCount: activeRecords.filter((r) => r.zoneId === z.id).length,
    }));

    return {
      activeOvertimeCount: activeRecords.length,
      totalEmployees,
      activeEmployees,
      zones: zonesWithCounts,
    };
  }
}
