import { Injectable, CanActivate, ExecutionContext, UnauthorizedException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { SystemConfig } from '@/database/entities/system-config.entity';

// Guard para endpoints externos que requieren X-API-Key
@Injectable()
export class ApiKeyGuard implements CanActivate {
  constructor(
    @InjectRepository(SystemConfig)
    private configRepo: Repository<SystemConfig>,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const request = context.switchToHttp().getRequest();
    const apiKey = request.headers['x-api-key'] as string;

    if (!apiKey) {
      throw new UnauthorizedException('Se requiere el header X-API-Key');
    }

    const config = await this.configRepo.findOne({ where: { key: 'cataleya_api_key' } });

    if (!config || config.value !== apiKey) {
      throw new UnauthorizedException('API Key inválida');
    }

    return true;
  }
}
