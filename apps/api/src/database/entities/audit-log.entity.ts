import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
} from 'typeorm';

@Entity('audit_logs')
export class AuditLog {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Column({ name: 'user_id', nullable: true })
  userId!: string | null;

  @Column({ length: 100 })
  action!: string;

  @Column({ length: 100 })
  entity!: string;

  @Column({ name: 'entity_id', nullable: true })
  entityId!: string | null;

  @Column({ name: 'old_value', type: 'jsonb', nullable: true })
  oldValue!: Record<string, unknown> | null;

  @Column({ name: 'new_value', type: 'jsonb', nullable: true })
  newValue!: Record<string, unknown> | null;

  @Column({ length: 45, nullable: true })
  ip!: string | null;

  @CreateDateColumn({ name: 'created_at' })
  createdAt!: Date;
}
