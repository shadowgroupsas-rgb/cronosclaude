import { registerAs } from '@nestjs/config';

export default registerAs('app', () => ({
  port: parseInt(process.env.PORT || '4000', 10),
  nodeEnv: process.env.NODE_ENV || 'development',
  appName: process.env.APP_NAME || 'Cronos',
  companyName: process.env.COMPANY_NAME || 'Copower Energy Solutions',
  frontendUrl: process.env.FRONTEND_URL || 'http://localhost:3000',
}));
