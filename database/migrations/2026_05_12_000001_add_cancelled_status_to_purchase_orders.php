<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateStatusConstraint(['draft', 'pending', 'needs_revision', 'approved', 'rejected', 'cancelled']);
    }

    public function down(): void
    {
        DB::table('purchase_orders')->where('status', 'cancelled')->update(['status' => 'draft']);

        $this->updateStatusConstraint(['draft', 'pending', 'needs_revision', 'approved', 'rejected']);
    }

    private function updateStatusConstraint(array $statuses): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            $this->updateSqlServerStatusConstraint($statuses);

            return;
        }

        $quotedStatuses = implode("','", $statuses);

        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('{$quotedStatuses}') NOT NULL DEFAULT 'draft'");
    }

    private function updateSqlServerStatusConstraint(array $statuses): void
    {
        $inList = implode(', ', array_map(
            static fn (string $status): string => "'{$status}'",
            $statuses
        ));

        DB::unprepared(<<<SQL
DECLARE @tableId INT = OBJECT_ID(N'purchase_orders');
DECLARE @columnId INT = COLUMNPROPERTY(@tableId, 'status', 'ColumnId');
DECLARE @dropSql NVARCHAR(MAX) = N'';

SELECT @dropSql = @dropSql + N'ALTER TABLE [purchase_orders] DROP CONSTRAINT [' + cc.name + N'];'
FROM sys.check_constraints AS cc
WHERE cc.parent_object_id = @tableId
  AND cc.parent_column_id = @columnId;

IF @dropSql <> N''
    EXEC sp_executesql @dropSql;

ALTER TABLE [purchase_orders]
ADD CONSTRAINT [purchase_orders_status_check]
CHECK ([status] IN ({$inList}));
SQL);
    }
};
