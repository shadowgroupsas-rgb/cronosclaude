import { SetMetadata } from '@nestjs/common';

export const ROLES_KEY = 'roles';

// Decorador para restringir acceso por slugs de rol
export const Roles = (...roles: string[]) => SetMetadata(ROLES_KEY, roles);
