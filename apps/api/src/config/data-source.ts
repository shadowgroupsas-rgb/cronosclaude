import { DataSource } from 'typeorm';
import * as dotenv from 'dotenv';

dotenv.config();

// DataSource para CLI de TypeORM (migraciones)
export default new DataSource({
  type: 'postgres',
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT || '5432', 10),
  username: process.env.DB_USERNAME || 'cronos_user',
  password: process.env.DB_PASSWORD || 'cronos_password',
  database: process.env.DB_DATABASE || 'cronos',
  entities: [__dirname + '/../database/entities/*.entity{.ts,.js}'],
  migrations: [__dirname + '/../database/migrations/*{.ts,.js}'],
  synchronize: false,
});
