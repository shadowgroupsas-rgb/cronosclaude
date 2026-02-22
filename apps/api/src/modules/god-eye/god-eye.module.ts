import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { GodEyeController } from './god-eye.controller';
import { GodEyeService } from './god-eye.service';
import { OvertimeRecord } from '@/database/entities/overtime-record.entity';
import { User } from '@/database/entities/user.entity';
import { Zone } from '@/database/entities/zone.entity';

@Module({
  imports: [TypeOrmModule.forFeature([OvertimeRecord, User, Zone])],
  controllers: [GodEyeController],
  providers: [GodEyeService],
})
export class GodEyeModule {}
