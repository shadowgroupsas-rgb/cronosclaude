import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from 'typeorm';
import { User } from './user.entity';
import { Zone } from './zone.entity';
import { LocationLog } from './location-log.entity';

export enum OvertimeStatus {
  ACTIVE = 'active',
  COMPLETED = 'completed',
  CANCELLED = 'cancelled',
}

@Entity('overtime_records')
export class OvertimeRecord {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Column({ name: 'user_id' })
  userId!: string;

  @ManyToOne(() => User, (user) => user.overtimeRecords)
  @JoinColumn({ name: 'user_id' })
  user!: User;

  @Column({ name: 'clock_in', type: 'timestamptz' })
  clockIn!: Date;

  @Column({ name: 'clock_out', type: 'timestamptz', nullable: true })
  clockOut!: Date | null;

  @Column({ name: 'clock_in_lat', type: 'decimal', precision: 10, scale: 7 })
  clockInLat!: number;

  @Column({ name: 'clock_in_lng', type: 'decimal', precision: 10, scale: 7 })
  clockInLng!: number;

  @Column({ name: 'clock_out_lat', type: 'decimal', precision: 10, scale: 7, nullable: true })
  clockOutLat!: number | null;

  @Column({ name: 'clock_out_lng', type: 'decimal', precision: 10, scale: 7, nullable: true })
  clockOutLng!: number | null;

  @Column({ type: 'text', nullable: true })
  description!: string | null;

  @Column({ type: 'enum', enum: OvertimeStatus, default: OvertimeStatus.ACTIVE })
  status!: OvertimeStatus;

  @Column({ name: 'is_nocturnal', default: false })
  isNocturnal!: boolean;

  @Column({ name: 'day_of_week', type: 'smallint' })
  dayOfWeek!: number;

  @Column({ name: 'total_minutes', type: 'int', nullable: true })
  totalMinutes!: number | null;

  @Column({ name: 'zone_id', nullable: true })
  zoneId!: string | null;

  @ManyToOne(() => Zone, { nullable: true })
  @JoinColumn({ name: 'zone_id' })
  zone!: Zone | null;

  @CreateDateColumn({ name: 'created_at' })
  createdAt!: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt!: Date;

  @OneToMany(() => LocationLog, (log) => log.overtimeRecord)
  locationLogs!: LocationLog[];
}
