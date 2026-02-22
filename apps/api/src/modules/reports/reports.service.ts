import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository, Between, SelectQueryBuilder } from 'typeorm';
import { OvertimeRecord, OvertimeStatus } from '@/database/entities/overtime-record.entity';
import { User } from '@/database/entities/user.entity';

export interface OvertimeReportFilter {
  userId?: string;
  departmentId?: string;
  from?: string;
  to?: string;
  period?: 'day' | 'week' | 'month';
}

export interface ReportRow {
  userId: string;
  firstName: string;
  lastName: string;
  email: string;
  department: string;
  totalRecords: number;
  totalMinutes: number;
  nocturnalRecords: number;
  nocturnalMinutes: number;
}

@Injectable()
export class ReportsService {
  private readonly logger = new Logger(ReportsService.name);

  constructor(
    @InjectRepository(OvertimeRecord) private overtimeRepo: Repository<OvertimeRecord>,
    @InjectRepository(User) private userRepo: Repository<User>,
  ) {}

  // Reporte consolidado de horas extras con filtros
  async getOvertimeReport(filter: OvertimeReportFilter): Promise<ReportRow[]> {
    const qb = this.overtimeRepo
      .createQueryBuilder('ot')
      .innerJoin('ot.user', 'user')
      .leftJoin('user.department', 'dept')
      .select([
        'user.id AS "userId"',
        'user.first_name AS "firstName"',
        'user.last_name AS "lastName"',
        'user.email AS "email"',
        'COALESCE(dept.name, \'Sin departamento\') AS "department"',
        'COUNT(ot.id)::int AS "totalRecords"',
        'COALESCE(SUM(ot.total_minutes), 0)::int AS "totalMinutes"',
        'SUM(CASE WHEN ot.is_nocturnal = true THEN 1 ELSE 0 END)::int AS "nocturnalRecords"',
        'SUM(CASE WHEN ot.is_nocturnal = true THEN COALESCE(ot.total_minutes, 0) ELSE 0 END)::int AS "nocturnalMinutes"',
      ])
      .where('ot.status = :status', { status: OvertimeStatus.COMPLETED });

    this.applyFilters(qb, filter);

    qb.groupBy('user.id, user.first_name, user.last_name, user.email, dept.name');
    qb.orderBy('"totalMinutes"', 'DESC');

    const result = await qb.getRawMany();
    return result as ReportRow[];
  }

  // Generar datos para exportación PDF
  async generatePdfData(filter: OvertimeReportFilter) {
    const report = await this.getOvertimeReport(filter);

    const totalMinutes = report.reduce((sum, r) => sum + r.totalMinutes, 0);
    const totalRecords = report.reduce((sum, r) => sum + r.totalRecords, 0);
    const totalNocturnal = report.reduce((sum, r) => sum + r.nocturnalRecords, 0);

    return {
      title: 'Reporte de Horas Extras - Cronos',
      company: 'Copower Energy Solutions',
      generatedAt: new Date().toISOString(),
      filter,
      summary: {
        totalEmployees: report.length,
        totalRecords,
        totalMinutes,
        totalHours: Math.round((totalMinutes / 60) * 100) / 100,
        totalNocturnal,
      },
      rows: report,
    };
  }

  // Generar PDF con pdfmake
  async generatePdf(filter: OvertimeReportFilter): Promise<Buffer> {
    const data = await this.generatePdfData(filter);
    const PdfPrinter = (await import('pdfmake')).default;

    const fonts = {
      Roboto: {
        normal: 'node_modules/pdfmake/build/vfs_fonts.js',
      },
    };

    // Crear tabla de datos
    const tableBody = [
      ['Empleado', 'Email', 'Departamento', 'Registros', 'Minutos', 'Nocturnos'],
      ...data.rows.map((r) => [
        `${r.firstName} ${r.lastName}`,
        r.email,
        r.department,
        r.totalRecords.toString(),
        r.totalMinutes.toString(),
        r.nocturnalRecords.toString(),
      ]),
    ];

    const docDefinition = {
      content: [
        { text: data.title, style: 'header' },
        { text: data.company, style: 'subheader' },
        { text: `Generado: ${new Date().toLocaleDateString('es-CO')}`, margin: [0, 0, 0, 10] as [number, number, number, number] },
        {
          text: `Total empleados: ${data.summary.totalEmployees} | Total registros: ${data.summary.totalRecords} | Total horas: ${data.summary.totalHours}h | Nocturnos: ${data.summary.totalNocturnal}`,
          margin: [0, 0, 0, 10] as [number, number, number, number],
        },
        {
          table: {
            headerRows: 1,
            widths: ['*', '*', '*', 'auto', 'auto', 'auto'],
            body: tableBody,
          },
        },
      ],
      styles: {
        header: { fontSize: 18, bold: true, margin: [0, 0, 0, 5] as [number, number, number, number] },
        subheader: { fontSize: 14, margin: [0, 0, 0, 10] as [number, number, number, number] },
      },
    };

    try {
      const printer = new PdfPrinter(fonts);
      const pdfDoc = printer.createPdfKitDocument(docDefinition as never);

      return new Promise<Buffer>((resolve, reject) => {
        const chunks: Uint8Array[] = [];
        pdfDoc.on('data', (chunk: Uint8Array) => chunks.push(chunk));
        pdfDoc.on('end', () => resolve(Buffer.concat(chunks)));
        pdfDoc.on('error', reject);
        pdfDoc.end();
      });
    } catch (error) {
      this.logger.error('Error generando PDF', error);
      // Fallback: devolver JSON como buffer
      return Buffer.from(JSON.stringify(data, null, 2));
    }
  }

  // Generar Excel con exceljs
  async generateExcel(filter: OvertimeReportFilter): Promise<Buffer> {
    const data = await this.generatePdfData(filter);
    const ExcelJS = await import('exceljs');
    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet('Horas Extras');

    // Encabezados
    sheet.columns = [
      { header: 'Empleado', key: 'name', width: 30 },
      { header: 'Email', key: 'email', width: 30 },
      { header: 'Departamento', key: 'department', width: 20 },
      { header: 'Total Registros', key: 'totalRecords', width: 15 },
      { header: 'Total Minutos', key: 'totalMinutes', width: 15 },
      { header: 'Total Horas', key: 'totalHours', width: 12 },
      { header: 'Registros Nocturnos', key: 'nocturnalRecords', width: 18 },
      { header: 'Minutos Nocturnos', key: 'nocturnalMinutes', width: 18 },
    ];

    // Estilo de encabezados
    const headerRow = sheet.getRow(1);
    headerRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
    headerRow.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF1B3A6B' },
    };

    // Datos
    data.rows.forEach((r) => {
      sheet.addRow({
        name: `${r.firstName} ${r.lastName}`,
        email: r.email,
        department: r.department,
        totalRecords: r.totalRecords,
        totalMinutes: r.totalMinutes,
        totalHours: Math.round((r.totalMinutes / 60) * 100) / 100,
        nocturnalRecords: r.nocturnalRecords,
        nocturnalMinutes: r.nocturnalMinutes,
      });
    });

    // Fila resumen
    sheet.addRow({});
    sheet.addRow({
      name: 'TOTAL',
      totalRecords: data.summary.totalRecords,
      totalMinutes: data.summary.totalMinutes,
      totalHours: data.summary.totalHours,
      nocturnalRecords: data.summary.totalNocturnal,
    });

    const buffer = await workbook.xlsx.writeBuffer();
    return Buffer.from(buffer);
  }

  private applyFilters(
    qb: SelectQueryBuilder<OvertimeRecord>,
    filter: OvertimeReportFilter,
  ): void {
    if (filter.userId) {
      qb.andWhere('ot.user_id = :userId', { userId: filter.userId });
    }
    if (filter.departmentId) {
      qb.andWhere('user.department_id = :departmentId', { departmentId: filter.departmentId });
    }
    if (filter.from && filter.to) {
      qb.andWhere('ot.clock_in BETWEEN :from AND :to', { from: filter.from, to: filter.to });
    } else if (filter.from) {
      qb.andWhere('ot.clock_in >= :from', { from: filter.from });
    } else if (filter.to) {
      qb.andWhere('ot.clock_in <= :to', { to: filter.to });
    }
  }
}
