import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { SystemConfig } from '@/database/entities/system-config.entity';

@Injectable()
export class SetupService {
  private readonly logger = new Logger(SetupService.name);

  constructor(
    @InjectRepository(SystemConfig) private readonly configRepo: Repository<SystemConfig>,
  ) {}

  async getAllConfigs(): Promise<SystemConfig[]> {
    return this.configRepo.find({ order: { key: 'ASC' } });
  }

  async getConfig(key: string): Promise<unknown> {
    const config = await this.configRepo.findOne({ where: { key } });
    return config?.value ?? null;
  }

  async setConfig(key: string, value: unknown): Promise<SystemConfig> {
    let config = await this.configRepo.findOne({ where: { key } });

    if (config) {
      config.value = value;
    } else {
      config = this.configRepo.create({ key, value });
    }

    const saved = await this.configRepo.save(config);
    this.logger.log(`Configuración actualizada: ${key}`);
    return saved;
  }

  async deleteConfig(key: string): Promise<void> {
    await this.configRepo.delete({ key });
    this.logger.log(`Configuración eliminada: ${key}`);
  }
}
