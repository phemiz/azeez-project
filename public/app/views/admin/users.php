<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="space-y-8 animate-fade-in">
    <!-- Breadcrumb Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 border-b border-slate-800 gap-4" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">Operator Account Directory</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Manage access, passwords, roles, and status levels for cellular security operators</span>
        </div>
        <button onclick="openCreateModal()" class="btn-primary text-xs py-2 px-4 cursor-pointer">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Add Operator</span>
        </button>
    </div>

    <!-- Search & Filters Toolbar -->
    <div class="cyber-card p-4">
        <form method="GET" action="<?= APP_URL ?>/admin/users" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Search User / Email</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search..." class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Filter Role</label>
                <select name="role" class="cyber-input py-1.5 text-xs">
                    <option value="">All Roles</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins</option>
                    <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>Standard Operators</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Filter Status</label>
                <select name="status" class="cyber-input py-1.5 text-xs">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    <option value="locked" <?= $statusFilter === 'locked' ? 'selected' : '' ?>>Locked</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-secondary w-full justify-center text-xs py-2">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Apply Filters</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Operators Table -->
    <div class="cyber-card p-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b font-mono text-[10px]" style="border-color: var(--color-border); color: var(--color-primary);">
                        <th class="pb-3">Username</th>
                        <th class="pb-3">Email</th>
                        <th class="pb-3">Secure Phone</th>
                        <th class="pb-3 text-center">Assigned Role</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3">Registered</th>
                        <th class="pb-3 text-right">Actions Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="divide-color: var(--color-border);">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="py-4 text-center" style="color: var(--color-foreground-muted);">No operator accounts found matching criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-800/10 transition-colors text-gray-300">
                                <td class="py-4 font-bold text-white font-mono"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="py-4 font-mono"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="py-4 font-mono"><?= htmlspecialchars($u['phone']) ?></td>
                                <td class="py-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono tracking-wider <?= $u['role'] === 'admin' ? 'bg-red-500/10 text-red-400' : 'bg-cyan-500/10 text-cyan-400' ?>">
                                        <?= strtoupper($u['role']) ?>
                                    </span>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono tracking-wider <?= $u['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <?= strtoupper($u['status']) ?>
                                    </span>
                                </td>
                                <td class="py-4 font-mono" style="color: var(--color-foreground-muted);"><?= date('Y-m-d H:i', strtotime($u['created_at'])) ?></td>
                                <td class="py-4 text-right space-x-1">
                                    <button onclick="openEditModal('<?= htmlspecialchars(json_encode($u)) ?>')" class="py-1 px-2 border border-cyan-500/30 hover:bg-cyan-500/10 text-cyan-400 rounded text-[10px] font-bold cursor-pointer font-mono">Edit</button>
                                    <button onclick="openResetModal(<?= $u['id'] ?>)" class="py-1 px-2 border border-amber-500/30 hover:bg-amber-500/10 text-amber-500 rounded text-[10px] font-bold cursor-pointer font-mono">Pass</button>
                                    
                                    <?php if ($u['id'] !== $user['id']): ?>
                                        <button onclick="toggleLockState(<?= $u['id'] ?>, '<?= $u['status'] === 'locked' ? 'unlock' : 'lock' ?>')" class="py-1 px-2 border border-slate-700 hover:bg-slate-800 text-gray-300 rounded text-[10px] font-bold cursor-pointer font-mono"><?= $u['status'] === 'locked' ? 'Unlock' : 'Lock' ?></button>
                                        <button onclick="deleteOperator(<?= $u['id'] ?>)" class="py-1 px-2 border border-rose-500/30 hover:bg-rose-500/10 text-rose-400 rounded text-[10px] font-bold cursor-pointer font-mono">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-between items-center mt-6 pt-4 border-t" style="border-color: var(--color-border); font-family: var(--font-family-mono); font-size: 11px; color: var(--color-foreground-muted);">
                <span>Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalUsers ?> users)</span>
                <div class="flex gap-2">
                    <a href="?search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>&page=<?= max(1, $page - 1) ?>" 
                       class="py-1 px-3 border border-slate-800 hover:border-cyan-500 rounded transition-all <?= $page === 1 ? 'pointer-events-none opacity-40' : '' ?>">Prev</a>
                    <a href="?search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>&page=<?= min($totalPages, $page + 1) ?>" 
                       class="py-1 px-3 border border-slate-800 hover:border-cyan-500 rounded transition-all <?= $page === $totalPages ? 'pointer-events-none opacity-40' : '' ?>">Next</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Create Operator -->
<div id="createModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="w-full max-w-md p-6 rounded-2xl border" style="background-color: var(--color-surface); border-color: var(--color-border);">
        <h3 class="text-sm font-bold text-white font-mono uppercase mb-4 border-b pb-2" style="border-color: var(--color-border);">Create Operator Account</h3>
        <form id="createForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Username</label>
                <input type="text" name="username" required class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Email</label>
                <input type="email" name="email" required class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Phone</label>
                <input type="text" name="phone" required placeholder="+2348030000000" class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Temporary Password</label>
                <input type="password" name="password" required class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Role</label>
                <select name="role" class="cyber-input py-1.5 text-xs">
                    <option value="user">Standard Operator</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t" style="border-color: var(--color-border);">
                <button type="button" onclick="closeModal('createModal')" class="btn-secondary text-[10px] py-1.5 px-3">Cancel</button>
                <button type="submit" class="btn-primary text-[10px] py-1.5 px-3">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Operator -->
<div id="editModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="w-full max-w-md p-6 rounded-2xl border" style="background-color: var(--color-surface); border-color: var(--color-border);">
        <h3 class="text-sm font-bold text-white font-mono uppercase mb-4 border-b pb-2" style="border-color: var(--color-border);">Modify Operator Account</h3>
        <form id="editForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="user_id" id="edit-id" />
            
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Username (Read-Only)</label>
                <input type="text" id="edit-username" disabled class="cyber-input py-1.5 text-xs opacity-60" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Email</label>
                <input type="email" name="email" id="edit-email" required class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Phone</label>
                <input type="text" name="phone" id="edit-phone" required class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Role</label>
                <select name="role" id="edit-role" class="cyber-input py-1.5 text-xs">
                    <option value="user">Standard Operator</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">Status</label>
                <select name="status" id="edit-status" class="cyber-input py-1.5 text-xs">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="locked">Locked</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t" style="border-color: var(--color-border);">
                <button type="button" onclick="closeModal('editModal')" class="btn-secondary text-[10px] py-1.5 px-3">Cancel</button>
                <button type="submit" class="btn-primary text-[10px] py-1.5 px-3">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password -->
<div id="resetModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="w-full max-w-sm p-6 rounded-2xl border" style="background-color: var(--color-surface); border-color: var(--color-border);">
        <h3 class="text-sm font-bold text-white font-mono uppercase mb-4 border-b pb-2" style="border-color: var(--color-border);">Deploy New Passcode</h3>
        <form id="resetForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="user_id" id="reset-id" />
            
            <div>
                <label class="block text-[10px] font-bold text-gray-400 mb-1">New Secure Password</label>
                <input type="password" name="password" required class="cyber-input py-1.5 text-xs" placeholder="••••••••" />
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t" style="border-color: var(--color-border);">
                <button type="button" onclick="closeModal('resetModal')" class="btn-secondary text-[10px] py-1.5 px-3">Cancel</button>
                <button type="submit" class="btn-primary text-[10px] py-1.5 px-3">Update Code</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}
function openCreateModal() {
    document.getElementById('createForm').reset();
    document.getElementById('createModal').classList.remove('hidden');
}
function openEditModal(userJson) {
    const u = JSON.parse(userJson);
    document.getElementById('edit-id').value = u.id;
    document.getElementById('edit-username').value = u.username;
    document.getElementById('edit-email').value = u.email;
    document.getElementById('edit-phone').value = u.phone;
    document.getElementById('edit-role').value = u.role;
    document.getElementById('edit-status').value = u.status;
    document.getElementById('editModal').classList.remove('hidden');
}
function openResetModal(userId) {
    document.getElementById('reset-id').value = userId;
    document.getElementById('resetForm').reset();
    document.getElementById('resetModal').classList.remove('hidden');
}

// 1. Submit Create Form
document.getElementById('createForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/admin/users/create', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (response.ok && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed'));
        }
    } catch (e) {
        alert('Network communications error.');
    }
});

// 2. Submit Edit Form
document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/admin/users/update', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (response.ok && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed'));
        }
    } catch (e) {
        alert('Network communications error.');
    }
});

// 3. Submit Reset Form
document.getElementById('resetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/admin/users/reset-password', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (response.ok && data.status === 'success') {
            alert(data.message);
            closeModal('resetModal');
        } else {
            alert('Error: ' + (data.message || 'Failed'));
        }
    } catch (e) {
        alert('Network communications error.');
    }
});

// 4. Toggle Lock Status
async function toggleLockState(userId, action) {
    if (!confirm(`Confirm Lock/Unlock state toggle for operator ID: ${userId}?`)) {
        return;
    }
    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('lock_action', action);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/users/toggle-lock', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (response.ok && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed'));
        }
    } catch (e) {
        alert('Network communications error.');
    }
}

// 5. Delete Operator Account
async function deleteOperator(userId) {
    if (!confirm(`WARNING: Deleting this account will permanently destroy all associated logs and encrypted envelopes. Confirm deletion of operator ID: ${userId}?`)) {
        return;
    }
    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/users/delete', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (response.ok && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed'));
        }
    } catch (e) {
        alert('Network communications error.');
    }
}
</script>
