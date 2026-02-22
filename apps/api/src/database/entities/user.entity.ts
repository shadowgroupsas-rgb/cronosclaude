import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  DeleteDateColumn,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from 'typeorm';
import { Role } from './role.entity';
import { Department } from './department.entity';
import { OvertimeRecord } from './overtime-record.entity';
import { Notification } from './notification.entity';
import { Exclude } from 'class-transformer';

@Entity('users')
export class User {
  @PrimaryGeneratedColumn('uuid')
  id!: string;

  @Column({ length: 255, unique: true })
  email!: string;

  @Column({ length: 255 })
  @Exclude()
  password!: string;

  @Column({ name: 'first_name', length: 100 })
  firstName!: string;

  @Column({ name: 'last_name', length: 100 })
  lastName!: string;

  @Column({ length: 20, nullable: true })
  phone!: string | null;

  @Column({ name: 'avatar_url', length: 500, nullable: true })
  avatarUrl!: string | null;

  @Column({ name: 'fcm_token', length: 500, nullable: true })
  fcmToken!: string | null;

  @Column({ name: 'role_id' })
  roleId!: string;

  @ManyToOne(() => Role, (role) => role.users, { eager: true })
  @JoinColumn({ name: 'role_id' })
  role!: Role;

  @Column({ name: 'department_id', nullable: true })
  departmentId!: string | null;

  @ManyToOne(() => Department, (dept) => dept.employees, { nullable: true })
  @JoinColumn({ name: 'department_id' })
  department!: Department | null;

  @Column({ name: 'is_active', default: true })
  isActive!: boolean;

  @Column({ name: 'refresh_token', length: 500, nullable: true })
  @Exclude()
  refreshToken!: string | null;

  @CreateDateColumn({ name: 'created_at' })
  createdAt!: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt!: Date;

  @DeleteDateColumn({ name: 'deleted_at' })
  deletedAt!: Date | null;

  @OneToMany(() => OvertimeRecord, (record) => record.user)
  overtimeRecords!: OvertimeRecord[];

  @OneToMany(() => Notification, (notification) => notification.user)
  notifications!: Notification[];

  get fullName(): string {
    return `${this.firstName} ${this.lastName}`;
  }
}
