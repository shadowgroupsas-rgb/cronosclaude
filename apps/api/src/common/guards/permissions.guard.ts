import { Injectable, CanActivate, ExecutionContext } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { PERMISSIONS_KEY } from '../decorators/permissions.decorator';
import { User } from '@/database/entities/user.entity';

@Injectable()
export class PermissionsGuard implements CanActivate {
  constructor(private reflector: Reflector) {}

  canActivate(context: ExecutionContext): boolean {
    const requiredPermissions = this.reflector.getAllAndOverride<string[]>(PERMISSIONS_KEY, [
      context.getHandler(),
      context.getClass(),
    ]);

    if (!requiredPermissions || requiredPermissions.length === 0) {
      return true;
    }

    const request = context.switchToHttp().getRequest();
    const user = request.user as User;

    if (!user || !user.role) {
      return false;
    }

    // Super admin tiene acceso total
    if (user.role.slug === 'super_admin') {
      return true;
    }

    const userPermissions = user.role.permissions || {};

    // Verificar que el usuario tenga todos los permisos requeridos
    return requiredPermissions.every((permission) => {
      // Formato: 'resource:action' o 'resource:action:scope'
      const parts = permission.split(':');
      const resource = parts[0];
      const action = parts.slice(1).join(':');

      const resourcePerms = userPermissions[resource];
      if (!resourcePerms || !Array.isArray(resourcePerms)) {
        return false;
      }

      return resourcePerms.includes(action) || resourcePerms.includes('*');
    });
  }
}
