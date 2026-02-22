import {
  Injectable,
  NotFoundException,
  Logger,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Zone } from '@/database/entities/zone.entity';
import { CreateZoneDto } from './dto/create-zone.dto';
import { UpdateZoneDto } from './dto/update-zone.dto';

@Injectable()
export class ZonesService {
  private readonly logger = new Logger(ZonesService.name);

  constructor(
    @InjectRepository(Zone) private readonly zoneRepo: Repository<Zone>,
  ) {}

  async findAll(): Promise<Zone[]> {
    return this.zoneRepo.find({ order: { name: 'ASC' } });
  }

  async findOne(id: string): Promise<Zone> {
    const zone = await this.zoneRepo.findOne({ where: { id } });
    if (!zone) {
      throw new NotFoundException(`Zona con ID ${id} no encontrada`);
    }
    return zone;
  }

  async create(dto: CreateZoneDto): Promise<Zone> {
    const zone = this.zoneRepo.create(dto);
    const saved = await this.zoneRepo.save(zone);
    this.logger.log(`Zona creada: ${saved.name} (ID: ${saved.id})`);
    return saved;
  }

  async update(id: string, dto: UpdateZoneDto): Promise<Zone> {
    const zone = await this.findOne(id);
    Object.assign(zone, dto);
    const updated = await this.zoneRepo.save(zone);
    this.logger.log(`Zona actualizada: ${updated.name}`);
    return updated;
  }

  async remove(id: string): Promise<void> {
    const zone = await this.findOne(id);
    await this.zoneRepo.remove(zone);
    this.logger.log(`Zona eliminada: ${zone.name}`);
  }
}
