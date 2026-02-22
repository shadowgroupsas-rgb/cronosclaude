import { Injectable, Logger, OnModuleInit } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import * as admin from 'firebase-admin';

@Injectable()
export class FirebaseService implements OnModuleInit {
  private readonly logger = new Logger(FirebaseService.name);
  private initialized = false;

  constructor(private configService: ConfigService) {}

  onModuleInit(): void {
    const projectId = this.configService.get<string>('FIREBASE_PROJECT_ID');
    const clientEmail = this.configService.get<string>('FIREBASE_CLIENT_EMAIL');
    const privateKey = this.configService.get<string>('FIREBASE_PRIVATE_KEY');

    if (projectId && clientEmail && privateKey) {
      try {
        admin.initializeApp({
          credential: admin.credential.cert({
            projectId,
            clientEmail,
            privateKey: privateKey.replace(/\\n/g, '\n'),
          }),
        });
        this.initialized = true;
        this.logger.log('Firebase Admin SDK inicializado correctamente');
      } catch (error) {
        this.logger.warn('No se pudo inicializar Firebase Admin SDK', error);
      }
    } else {
      this.logger.warn('Firebase Admin SDK no configurado - notificaciones push deshabilitadas');
    }
  }

  async sendPushNotification(
    fcmToken: string,
    title: string,
    body: string,
    data?: Record<string, string>,
  ): Promise<boolean> {
    if (!this.initialized) {
      this.logger.warn('Firebase no inicializado, notificación no enviada');
      return false;
    }

    try {
      await admin.messaging().send({
        token: fcmToken,
        notification: { title, body },
        data: data || {},
        android: { priority: 'high' },
        apns: { payload: { aps: { sound: 'default' } } },
      });
      this.logger.log(`Notificación push enviada a token: ${fcmToken.substring(0, 10)}...`);
      return true;
    } catch (error) {
      this.logger.error('Error al enviar notificación push', error);
      return false;
    }
  }

  async sendToMultiple(
    fcmTokens: string[],
    title: string,
    body: string,
    data?: Record<string, string>,
  ): Promise<number> {
    if (!this.initialized || fcmTokens.length === 0) return 0;

    let successCount = 0;
    for (const token of fcmTokens) {
      const sent = await this.sendPushNotification(token, title, body, data);
      if (sent) successCount++;
    }
    return successCount;
  }
}
