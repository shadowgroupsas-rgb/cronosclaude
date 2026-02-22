import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ReportsController } from './reports.controller';
import { ReportsService } from './reports.service';
import { OvertimeRecord } from '@/database/entities/overtime-record.entity';
import { User } from '@/database/entities/user.entity';

@Module({
  imports: [TypeOrmModule.forFeature([OvertimeRecord, User])],
  controllers: [ReportsController],
  providers: [ReportsService],
})
export class ReportsModule {}
