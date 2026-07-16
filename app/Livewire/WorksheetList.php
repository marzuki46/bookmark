<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;

final class WorksheetList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public bool $showModal = false;

    public bool $editMode = false;

    public ?int $editingId = null;

    public string $formTitle = '';

    public bool $formFavorite = false;

    public array $formColumns = ['Item', 'Qty', 'Harga'];

    public array $formColumnTypes = ['text', 'number', 'number'];

    public array $formRows = [];

    public array $formChecklist = [];

    public bool $formShowTable = true;

    public bool $formShowChecklist = true;

    protected string $paginationTheme = 'tailwind';

    public function rules(): array
    {
        return [
            'formTitle' => 'required|string|max:255',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->formRows = [['', '', '']];
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'worksheet')->findOrFail($id);
        $meta = $item->metadata ?? [];

        $this->editingId = $id;
        $this->editMode = true;
        $this->formTitle = $item->title ?? '';
        $this->formFavorite = $item->favorite;
        $this->formColumns = $meta['columns'] ?? ['Item', 'Qty', 'Harga'];
        $this->formColumnTypes = $meta['columnTypes'] ?? ['text', 'number', 'number'];
        $this->formRows = $meta['rows'] ?? [['', '', '']];
        $this->formChecklist = $meta['checklist'] ?? [];
        $this->formShowTable = $meta['showTable'] ?? true;
        $this->formShowChecklist = $meta['showChecklist'] ?? true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $metadata = [
            'columns' => $this->formColumns,
            'columnTypes' => $this->formColumnTypes,
            'rows' => $this->formRows,
            'checklist' => $this->formChecklist,
            'showTable' => $this->formShowTable,
            'showChecklist' => $this->formShowChecklist,
        ];

        $data = [
            'user_id' => auth()->id(),
            'type' => 'worksheet',
            'title' => $this->formTitle,
            'metadata' => $metadata,
            'favorite' => $this->formFavorite,
        ];

        if ($this->editMode && $this->editingId) {
            $item = Item::where('user_id', auth()->id())->where('type', 'worksheet')->findOrFail($this->editingId);
            $item->update($data);
        } else {
            Item::create($data);
        }

        $this->closeModal();
    }

    public function toggleFavorite(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'worksheet')->findOrFail($id);
        $item->update(['favorite' => ! $item->favorite]);
    }

    public function trash(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'worksheet')->findOrFail($id);
        $item->delete();
    }

    // ─── Table operations ───

    public function addColumn(): void
    {
        $this->formColumns[] = '';
        $this->formColumnTypes[] = 'text';
        foreach ($this->formRows as &$row) {
            $row[] = '';
        }
    }

    public function removeColumn(int $index): void
    {
        if (count($this->formColumns) <= 1) {
            return;
        }
        array_splice($this->formColumns, $index, 1);
        array_splice($this->formColumnTypes, $index, 1);
        foreach ($this->formRows as &$row) {
            array_splice($row, $index, 1);
        }
    }

    public function updateColumnName(int $index, string $value): void
    {
        $this->formColumns[$index] = $value;
    }

    public function updateColumnType(int $index, string $value): void
    {
        $this->formColumnTypes[$index] = $value;
    }

    public function addRow(): void
    {
        $cols = count($this->formColumns);
        $row = array_fill(0, $cols, '');
        $this->formRows[] = $row;
    }

    public function removeRow(int $index): void
    {
        if (count($this->formRows) <= 1) {
            return;
        }
        array_splice($this->formRows, $index, 1);
    }

    public function updateCell(int $rowIndex, int $colIndex, string $value): void
    {
        if (isset($this->formRows[$rowIndex][$colIndex])) {
            $this->formRows[$rowIndex][$colIndex] = $value;
        }
    }

    // ─── Checklist operations ───

    public function addChecklistItem(): void
    {
        $this->formChecklist[] = ['text' => '', 'checked' => false];
    }

    public function removeChecklistItem(int $index): void
    {
        array_splice($this->formChecklist, $index, 1);
    }

    public function updateChecklistText(int $index, string $value): void
    {
        if (isset($this->formChecklist[$index])) {
            $this->formChecklist[$index]['text'] = $value;
        }
    }

    public function toggleChecklistItem(int $index): void
    {
        if (isset($this->formChecklist[$index])) {
            $this->formChecklist[$index]['checked'] = ! $this->formChecklist[$index]['checked'];
        }
    }

    // ─── View-only checklist toggle (on card, not in modal) ───

    public function toggleCheckItem(int $itemId, int $checkIndex): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'worksheet')->findOrFail($itemId);
        $meta = $item->metadata ?? [];

        if (isset($meta['checklist'][$checkIndex])) {
            $meta['checklist'][$checkIndex]['checked'] = ! $meta['checklist'][$checkIndex]['checked'];
            $item->update(['metadata' => $meta]);
        }
    }

    // ─── Helpers ───

    public function getColumnTotal(array $rows, int $colIndex, string $type): ?float
    {
        if ($type !== 'number') {
            return null;
        }

        $total = 0.0;
        foreach ($rows as $row) {
            $val = (float) ($row[$colIndex] ?? 0);
            $total += $val;
        }

        return $total;
    }

    public function getChecklistProgress(array $checklist): int
    {
        if (empty($checklist)) {
            return 0;
        }

        $checked = count(array_filter($checklist, fn ($c) => $c['checked']));

        return (int) round(($checked / count($checklist)) * 100);
    }

    public function render()
    {
        $query = Item::with(['tags'])
            ->where('user_id', auth()->id())
            ->where('type', 'worksheet');

        match ($this->filter) {
            'favorites' => $query->where('favorite', true),
            default => $query->whereNull('archived_at'),
        };

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%");
            });
        }

        $worksheets = $query->latest()->paginate(12);

        $stats = [
            'total' => Item::where('user_id', auth()->id())->where('type', 'worksheet')->count(),
            'favorites' => Item::where('user_id', auth()->id())->where('type', 'worksheet')->where('favorite', true)->count(),
        ];

        return view('livewire.worksheet-list', compact('worksheets', 'stats'));
    }

    private function resetForm(): void
    {
        $this->formTitle = '';
        $this->formFavorite = false;
        $this->formColumns = ['Item', 'Qty', 'Harga'];
        $this->formColumnTypes = ['text', 'number', 'number'];
        $this->formRows = [['', '', '']];
        $this->formChecklist = [];
        $this->formShowTable = true;
        $this->formShowChecklist = true;
        $this->editingId = null;
        $this->editMode = false;
        $this->clearValidation();
    }
}
