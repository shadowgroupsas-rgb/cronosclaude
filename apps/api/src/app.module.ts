import { Module } from '@nestjs/common';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ThrottlerModule } from '@nestjs/throttler';

// Configuraciones
import appConfig from './config/app.config';
import databaseConfig from './config/database.config';
import jwtConfig from './config/jwt.config';

// Módulos compartidos (globales)
import { S3Module } from './shared/s3/s3.module';
import { FirebaseModule } from './shared/firebase/firebase.module';

// Módulos de la aplicación
import { AuthModule } from './modules/auth/auth.module';
import { UsersModule } from './modules/users/users.module';
import { RolesModule } from './modules/roles/roles.module';
import { DepartmentsModule } from './modules/departments/departments.module';
import { OvertimeModule } from './modules/overtime/overtime.module';
import { TrackingModule } from './modules/tracking/tracking.module';
import { ZonesModule } from './modules/zones/zones.module';
import { GodEyeModule } from './modules/god-eye/god-eye.module';
import { ReportsModule } from './modules/reports/reports.module';
import { NotificationsModule } from './modules/notifications/notifications.module';
import { SetupModule } from './modules/setup/setup.module';
import { ExternalModule } from './modules/external/external.module';
import { AuditModule } from './modules/audit/audit.module';

@Module({
  imports: [
    // Configuración global
    ConfigModule.forRoot({
      isGlobal: true,
      load: [appConfig, databaseConfig, jwtConfig],
      envFilePath: ['.env', '.env.local'],
    }),

    // Base de datos PostgreSQL con TypeORM
    TypeOrmModule.forRootAsync({
      imports: [ConfigModule],
      inject: [ConfigService],
      useFactory: (configService: ConfigService) => ({
        type: 'postgres' as const,
        host: configService.get<string>('database.host', 'localhost'),
        port: configService.get<number>('database.port', 5432),
        username: configService.get<string>('database.username', 'cronos_user'),
        password: configService.get<string>('database.password', 'cronos_password'),
        database: configService.get<string>('database.database', 'cronos'),
        entities: [__dirname + '/database/entities/*.entity{.ts,.js}'],
        migrations: [__dirname + '/database/migrations/*{.ts,.js}'],
        synchronize: configService.get<string>('app.nodeEnv') === 'development',
        logging: configService.get<string>('app.nodeEnv') === 'development',
      }),
    }),

    // Rate limiting
    ThrottlerModule.forRoot([{ ttl: 60000, limit: 100 }]),

    // Módulos globales
    S3Module,
    FirebaseModule,
    AuditModule,

    // Módulos de aplicación
    AuthModule,
    UsersModule,
    RolesModule,
    DepartmentsModule,
    OvertimeModule,
    TrackingModule,
    ZonesModule,
    GodEyeModule,
    ReportsModule,
    NotificationsModule,
    SetupModule,
    ExternalModule,
  ],
})
export class AppModule {}
