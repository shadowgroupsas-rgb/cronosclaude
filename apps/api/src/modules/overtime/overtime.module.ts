import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { OvertimeController } from './overtime.controller';
import { OvertimeService } from './overtime.service';
import { OvertimeRecord } from '@/database/entities/overtime-record.entity';
import { Zone } from '@/database/entities/zone.entity';
import { LocationLog } from '@/database/entities/location-log.entity';

@Module({
  imports: [TypeOrmModule.forFeature([OvertimeRecord, Zone, LocationLog])],
  controllers: [OvertimeController],
  providers: [OvertimeService],
  exports: [OvertimeService],
})
export class OvertimeModule {}
