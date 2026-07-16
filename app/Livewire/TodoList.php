<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithPagination;

final class TodoList extends Component
{
    use WithPagination;

    public string $formTitle = '';

    public string $formDescription = '';

    public string $formPriority = 'medium';

    public string $formDueDate = '';

    public int $editId = 0;

    public bool $showModal = false;

    public string $filterStatus = 'all';

    public string $search = '';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public int $totalTodos = 0;

    public int $completedTodos = 0;

    public int $pendingTodos = 0;

    public int $overdueTodos = 0;

    protected function rules(): array
    {
        return [
            'formTitle' => 'required|string|min:1|max:500',
            'formDescription' => 'nullable|string|max:5000',
            'formPriority' => 'required|in:low,medium,high',
            'formDueDate' => 'nullable|date',
        ];
    }

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function openModal(): void
    {
        $this->resetForm();
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

        $data = [
            'user_id' => auth()->id(),
            'type' => 'todo',
            'title' => trim($this->formTitle),
            'content' => trim($this->formDescription) ?: null,
            'metadata' => [
                'priority' => $this->formPriority,
                'due_date' => $this->formDueDate ?: null,
                'completed' => false,
            ],
        ];

        if ($this->editId > 0) {
            $item = Item::where('id', $this->editId)->where('user_id', auth()->id())->where('type', 'todo')->first();

            if ($item) {
                $existingMeta = $item->metadata ?? [];
                $data['metadata'] = array_merge($existingMeta, $data['metadata']);
                $data['metadata']['priority'] = $this->formPriority;
                $data['metadata']['due_date'] = $this->formDueDate ?: null;
                $item->update($data);

                $this->statusMessage = 'Todo updated successfully.';
            }
        } else {
            Item::create($data);
            $this->statusMessage = 'Todo created successfully.';
        }

        $this->statusType = 'success';
        $this->showModal = false;
        $this->resetForm();
        $this->refreshStats();
    }

    public function toggleComplete(int $id): void
    {
        $item = Item::where('id', $id)->where('user_id', auth()->id())->where('type', 'todo')->first();

        if ($item) {
            $meta = $item->metadata ?? [];
            $meta['completed'] = ! ($meta['completed'] ?? false);
            $item->update(['metadata' => $meta]);
            $this->refreshStats();
        }
    }

    public function editTodo(int $id): void
    {
        $item = Item::where('id', $id)->where('user_id', auth()->id())->where('type', 'todo')->first();

        if ($item) {
            $meta = $item->metadata ?? [];
            $this->editId = $id;
            $this->formTitle = $item->title ?? '';
            $this->formDescription = $item->content ?? '';
            $this->formPriority = $meta['priority'] ?? 'medium';
            $this->formDueDate = $meta['due_date'] ?? '';
            $this->showModal = true;
        }
    }

    public function deleteTodo(int $id): void
    {
        Item::where('id', $id)->where('user_id', auth()->id())->where('type', 'todo')->delete();

        $this->statusMessage = 'Todo deleted.';
        $this->statusType = 'success';
        $this->refreshStats();
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    private function resetForm(): void
    {
        $this->editId = 0;
        $this->formTitle = '';
        $this->formDescription = '';
        $this->formPriority = 'medium';
        $this->formDueDate = '';
    }

    private function refreshStats(): void
    {
        $userId = auth()->id();
        $query = Item::where('user_id', $userId)->where('type', 'todo');

        $this->totalTodos = (clone $query)->count();

        $completedQuery = (clone $query)->whereJsonContains('metadata->completed', true);
        $this->completedTodos = $completedQuery->count();

        $this->pendingTodos = $this->totalTodos - $this->completedTodos;

        $this->overdueTodos = (clone $query)
            ->whereJsonContains('metadata->completed', false)
            ->whereNotNull('metadata->due_date')
            ->where('metadata->due_date', '<', now()->toDateString())
            ->count();
    }

    public function getTodosProperty()
    {
        $userId = auth()->id();
        $query = Item::where('user_id', $userId)->where('type', 'todo');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('content', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterStatus === 'completed') {
            $query->whereJsonContains('metadata->completed', true);
        } elseif ($this->filterStatus === 'pending') {
            $query->whereJsonContains('metadata->completed', false);
        } elseif ($this->filterStatus === 'overdue') {
            $query->whereJsonContains('metadata->completed', false)
                ->whereNotNull('metadata->due_date')
                ->where('metadata->due_date', '<', now()->toDateString());
        }

        return $query->latest()->paginate(15);
    }

    public function render()
    {
        return view('livewire.todo-list', [
            'todos' => $this->todos,
        ]);
    }
}
