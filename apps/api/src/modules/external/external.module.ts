import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ExternalController } from './external.controller';
import { OvertimeRecord } from '@/database/entities/overtime-record.entity';
import { SystemConfig } from '@/database/entities/system-config.entity';

@Module({
  imports: [TypeOrmModule.forFeature([OvertimeRecord, SystemConfig])],
  controllers: [ExternalController],
})
export class ExternalModule {}
