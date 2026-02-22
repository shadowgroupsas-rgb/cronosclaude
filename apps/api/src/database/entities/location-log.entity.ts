import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { OvertimeRecord } from './overtime-record.entity';

@Entity('location_logs')
export class LocationLog {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Column({ name: 'overtime_record_id' })
  overtimeRecordId!: string;

  @ManyToOne(() => OvertimeRecord, (record) => record.locationLogs, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'overtime_record_id' })
  overtimeRecord!: OvertimeRecord;

  @Column({ type: 'decimal', precision: 10, scale: 7 })
  latitude!: number;

  @Column({ type: 'decimal', precision: 10, scale: 7 })
  longitude!: number;

  @Column({ type: 'decimal', precision: 6, scale: 2, nullable: true })
  accuracy!: number | null;

  @Column({ type: 'timestamptz' })
  timestamp!: Date;
}
