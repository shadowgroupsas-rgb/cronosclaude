import { Injectable, NotFoundException, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Notification } from '@/database/entities/notification.entity';
import { User } from '@/database/entities/user.entity';
import { FirebaseService } from '@/shared/firebase/firebase.service';

@Injectable()
export class NotificationsService {
  private readonly logger = new Logger(NotificationsService.name);

  constructor(
    @InjectRepository(Notification) private readonly notifRepo: Repository<Notification>,
    @InjectRepository(User) private readonly userRepo: Repository<User>,
    private readonly firebaseService: FirebaseService,
  ) {}

  async getUserNotifications(userId: string): Promise<Notification[]> {
    return this.notifRepo.find({
      where: { userId },
      order: { createdAt: 'DESC' },
      take: 50,
    });
  }

  async getUnreadCount(userId: string): Promise<number> {
    return this.notifRepo.count({ where: { userId, isRead: false } });
  }

  async markAsRead(userId: string, notificationId: string): Promise<Notification> {
    const notif = await this.notifRepo.findOne({
      where: { id: notificationId, userId },
    });

    if (!notif) {
      throw new NotFoundException('Notificación no encontrada');
    }

    notif.isRead = true;
    return this.notifRepo.save(notif);
  }

  async markAllAsRead(userId: string): Promise<void> {
    await this.notifRepo.update({ userId, isRead: false }, { isRead: true });
  }

  async createAndSend(
    userId: string,
    title: string,
    body: string,
    type: string = 'general',
    metadata?: Record<string, unknown>,
  ): Promise<Notification> {
    const notif = this.notifRepo.create({
      userId,
      title,
      body,
      type,
      metadata: metadata || null,
    });

    const saved = await this.notifRepo.save(notif);

    // Enviar push si el usuario tiene FCM token
    const user = await this.userRepo.findOne({ where: { id: userId } });
    if (user?.fcmToken) {
      await this.firebaseService.sendPushNotification(user.fcmToken, title, body);
    }

    this.logger.log(`Notificación creada para usuario ${userId}: ${title}`);
    return saved;
  }

  async sendToAll(title: string, body: string, type: string = 'general'): Promise<number> {
    const users = await this.userRepo.find({ where: { isActive: true } });
    let count = 0;

    for (const user of users) {
      await this.createAndSend(user.id, title, body, type);
      count++;
    }

    return count;
  }
}
