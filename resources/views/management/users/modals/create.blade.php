<div class="modal fade" id="userCreateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="userCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="userCreateModalLabel">Create a new user</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('caronte.management.users.store') }}" id="userCreateForm">
                    @csrf
                    <div class="mb-3">
                        <label for="createNameInput" class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control" id="createNameInput" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="createEmailInput" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="createEmailInput" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="createPasswordInput" class="form-label fw-semibold">Temporary password</label>
                        <input type="password" class="form-control" id="createPasswordInput" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="createPasswordConfirmationInput" class="form-label fw-semibold">Confirm password</label>
                        <input type="password" class="form-control" id="createPasswordConfirmationInput" name="password_confirmation" required>
                    </div>

                    @include('caronte::management.users.partials.roles-checkboxes', ['idPrefix' => 'create'])

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-2"></i>Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-plus me-2"></i>Create user
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
