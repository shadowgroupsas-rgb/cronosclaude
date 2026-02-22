import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { TrackingController } from './tracking.controller';
import { TrackingService } from './tracking.service';
import { LocationLog } from '@/database/entities/location-log.entity';
import { OvertimeRecord } from '@/database/entities/overtime-record.entity';

@Module({
  imports: [TypeOrmModule.forFeature([LocationLog, OvertimeRecord])],
  controllers: [TrackingController],
  providers: [TrackingService],
  exports: [TrackingService],
})
export class TrackingModule {}
