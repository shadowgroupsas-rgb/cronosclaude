import { DataSource } from 'typeorm';
import * as bcrypt from 'bcrypt';
import dataSource from '../../config/data-source';

async function runSeed() {
  console.log('🌱 Iniciando seed de datos...');

  const ds: DataSource = dataSource;
  await ds.initialize();

  const queryRunner = ds.createQueryRunner();
  await queryRunner.connect();
  await queryRunner.startTransaction();

  try {
    // === ROLES ===
    console.log('📋 Creando roles...');
    const roleRepo = queryRunner.manager.getRepository('roles');

    const roles = [
      {
        name: 'Super Administrador',
        slug: 'super_admin',
        description: 'Acceso total al sistema, gestión completa',
        permissions: { '*': ['*'] },
        isSystem: true,
      },
      {
        name: 'Administrador',
        slug: 'admin',
        description: 'Gestión de usuarios, departamentos, reportes y configuración',
        permissions: {
          users: ['read', 'create', 'update', 'delete'],
          roles: ['read'],
          departments: ['read', 'create', 'update', 'delete'],
          overtime: ['read', 'create', 'update'],
          tracking: ['read'],
          zones: ['read', 'create', 'update', 'delete'],
          reports: ['read', 'export'],
          notifications: ['read', 'create'],
        },
        isSystem: true,
      },
      {
        name: 'Supervisor',
        slug: 'supervisor',
        description: 'Supervisión de empleados de su departamento y reportes',
        permissions: {
          users: ['read'],
          departments: ['read'],
          overtime: ['read'],
          tracking: ['read'],
          zones: ['read'],
          reports: ['read', 'export'],
        },
        isSystem: true,
      },
      {
        name: 'Empleado',
        slug: 'employee',
        description: 'Registro de horas extras y visualización de su historial',
        permissions: {
          overtime: ['read', 'create'],
          notifications: ['read'],
        },
        isSystem: true,
      },
    ];

    const savedRoles: Record<string, string> = {};
    for (const role of roles) {
      const existing = await roleRepo.findOne({ where: { slug: role.slug } });
      if (!existing) {
        const saved = await roleRepo.save(role);
        savedRoles[role.slug] = (saved as { id: string }).id;
        console.log(`  ✅ Rol creado: ${role.name}`);
      } else {
        savedRoles[role.slug] = (existing as { id: string }).id;
        console.log(`  ⏭️  Rol ya existe: ${role.name}`);
      }
    }

    // === DEPARTAMENTOS ===
    console.log('\n🏢 Creando departamentos...');
    const deptRepo = queryRunner.manager.getRepository('departments');

    const departments = [
      { name: 'Dirección General', description: 'Alta dirección de la empresa' },
      { name: 'Operaciones', description: 'Departamento de operaciones y logística' },
      { name: 'Recursos Humanos', description: 'Gestión del talento humano' },
      { name: 'Tecnología', description: 'Desarrollo tecnológico e innovación' },
      { name: 'Mantenimiento', description: 'Mantenimiento de infraestructura y equipos' },
    ];

    const savedDepts: Record<string, string> = {};
    for (const dept of departments) {
      const existing = await deptRepo.findOne({ where: { name: dept.name } });
      if (!existing) {
        const saved = await deptRepo.save(dept);
        savedDepts[dept.name] = (saved as { id: string }).id;
        console.log(`  ✅ Departamento creado: ${dept.name}`);
      } else {
        savedDepts[dept.name] = (existing as { id: string }).id;
        console.log(`  ⏭️  Departamento ya existe: ${dept.name}`);
      }
    }

    // === USUARIOS ===
    console.log('\n👤 Creando usuarios...');
    const userRepo = queryRunner.manager.getRepository('users');
    const hashedPassword = await bcrypt.hash('Cronos2026!', 12);

    const users = [
      {
        email: 'admin@cronos.test',
        password: hashedPassword,
        firstName: 'Admin',
        lastName: 'Sistema',
        phone: '+18091234567',
        roleId: savedRoles['super_admin'],
        departmentId: savedDepts['Dirección General'],
        isActive: true,
      },
      {
        email: 'rrhh@cronos.test',
        password: hashedPassword,
        firstName: 'Maria',
        lastName: 'González',
        phone: '+18092345678',
        roleId: savedRoles['admin'],
        departmentId: savedDepts['Recursos Humanos'],
        isActive: true,
      },
      {
        email: 'supervisor@cronos.test',
        password: hashedPassword,
        firstName: 'Carlos',
        lastName: 'Ramírez',
        phone: '+18093456789',
        roleId: savedRoles['supervisor'],
        departmentId: savedDepts['Operaciones'],
        isActive: true,
      },
      {
        email: 'empleado@cronos.test',
        password: hashedPassword,
        firstName: 'Juan',
        lastName: 'Pérez',
        phone: '+18094567890',
        roleId: savedRoles['employee'],
        departmentId: savedDepts['Operaciones'],
        isActive: true,
      },
    ];

    for (const user of users) {
      const existing = await userRepo.findOne({ where: { email: user.email } });
      if (!existing) {
        await userRepo.save(user);
        console.log(`  ✅ Usuario creado: ${user.email}`);
      } else {
        console.log(`  ⏭️  Usuario ya existe: ${user.email}`);
      }
    }

    // === ZONAS ===
    console.log('\n📍 Creando zonas de ejemplo...');
    const zoneRepo = queryRunner.manager.getRepository('zones');

    const zones = [
      {
        name: 'Oficina Central',
        description: 'Edificio principal de Copower Energy Solutions',
        polygon: [
          { lat: 18.4870, lng: -69.9320 },
          { lat: 18.4870, lng: -69.9300 },
          { lat: 18.4850, lng: -69.9300 },
          { lat: 18.4850, lng: -69.9320 },
        ],
        color: '#1B3A6B',
        isActive: true,
      },
      {
        name: 'Planta de Operaciones',
        description: 'Zona de planta de operaciones y mantenimiento',
        polygon: [
          { lat: 18.4900, lng: -69.9280 },
          { lat: 18.4900, lng: -69.9260 },
          { lat: 18.4880, lng: -69.9260 },
          { lat: 18.4880, lng: -69.9280 },
        ],
        color: '#CC2229',
        isActive: true,
      },
    ];

    for (const zone of zones) {
      const existing = await zoneRepo.findOne({ where: { name: zone.name } });
      if (!existing) {
        await zoneRepo.save(zone);
        console.log(`  ✅ Zona creada: ${zone.name}`);
      } else {
        console.log(`  ⏭️  Zona ya existe: ${zone.name}`);
      }
    }

    // === CONFIGURACIÓN DEL SISTEMA ===
    console.log('\n⚙️  Creando configuración del sistema...');
    const configRepo = queryRunner.manager.getRepository('system_config');

    const configs = [
      { key: 'nocturnal_start_hour', value: 19 },
      { key: 'nocturnal_end_hour', value: 6 },
      { key: 'min_overtime_minutes', value: 30 },
      { key: 'tracking_interval_seconds', value: 60 },
      { key: 'company_name', value: 'Copower Energy Solutions' },
      { key: 'company_timezone', value: 'America/Santo_Domingo' },
    ];

    for (const config of configs) {
      const existing = await configRepo.findOne({ where: { key: config.key } });
      if (!existing) {
        await configRepo.save(config);
        console.log(`  ✅ Config creada: ${config.key}`);
      } else {
        console.log(`  ⏭️  Config ya existe: ${config.key}`);
      }
    }

    await queryRunner.commitTransaction();
    console.log('\n🎉 Seed completado exitosamente!');
    console.log('\n📧 Credenciales de prueba:');
    console.log('   Email: admin@cronos.test');
    console.log('   Password: Cronos2026!');
  } catch (error) {
    await queryRunner.rollbackTransaction();
    console.error('❌ Error durante el seed:', error);
    throw error;
  } finally {
    await queryRunner.release();
    await ds.destroy();
  }
}

runSeed().catch((err) => {
  console.error(err);
  process.exit(1);
});
