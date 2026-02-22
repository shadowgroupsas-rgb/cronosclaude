import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { AuditLog } from '@/database/entities/audit-log.entity';

@Injectable()
export class AuditService {
  private readonly logger = new Logger(AuditService.name);

  constructor(
    @InjectRepository(AuditLog) private readonly auditRepo: Repository<AuditLog>,
  ) {}

  async log(
    action: string,
    entity: string,
    entityId?: string,
    userId?: string,
    oldValue?: Record<string, unknown>,
    newValue?: Record<string, unknown>,
    ip?: string,
  ): Promise<AuditLog> {
    const audit = this.auditRepo.create({
      action,
      entity,
      entityId: entityId || null,
      userId: userId || null,
      oldValue: oldValue || null,
      newValue: newValue || null,
      ip: ip || null,
    });

    return this.auditRepo.save(audit);
  }

  async findAll(options?: { entity?: string; userId?: string; limit?: number }): Promise<AuditLog[]> {
    const qb = this.auditRepo.createQueryBuilder('audit');

    if (options?.entity) {
      qb.andWhere('audit.entity = :entity', { entity: options.entity });
    }
    if (options?.userId) {
      qb.andWhere('audit.userId = :userId', { userId: options.userId });
    }

    qb.orderBy('audit.createdAt', 'DESC');
    qb.take(options?.limit || 100);

    return qb.getMany();
  }
}
