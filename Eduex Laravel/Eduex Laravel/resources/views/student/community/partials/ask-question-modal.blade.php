<div class="modal fade" id="askQuestionModal" tabindex="-1" aria-labelledby="askQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="border-bottom: 1px solid #f1f1f1; padding: 25px 30px;">
                <h5 class="modal-title" id="askQuestionModalLabel" style="font-weight: 800; color: #002b24;">{{ __('student.ask_question') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('student.community.store') }}" method="POST">
                @csrf
                <div class="modal-body" style="padding: 30px;">
                    <div class="mb-4">
                        <label for="title" class="form-label" style="font-weight: 700; color: #002b24;">{{ __('student.question') }}</label>
                        <input type="text" class="form-control" id="title" name="title"
                            placeholder="e.g. How do I implement Laravel Sanctum?" required
                            style="border-radius: 12px; padding: 12px 18px; border: 1px solid #ddd;">
                        <small class="text-muted">Be specific and imagine you're asking a person.</small>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label"
                            style="font-weight: 700; color: #002b24;">{{ __('student.description') }}</label>
                        <textarea class="form-control" id="description" name="description" rows="5"
                            placeholder="Include all the information someone would need to answer your question"
                            required
                            style="border-radius: 12px; padding: 12px 18px; border: 1px solid #ddd;"></textarea>
                    </div>

                    <div class="mb-0">
                        <label for="tags" class="form-label" style="font-weight: 700; color: #002b24;">{{ __('student.topic') }}</label>
                        <input type="text" class="form-control" id="tags" name="tags" placeholder="laravel, php, auth"
                            style="border-radius: 12px; padding: 12px 18px; border: 1px solid #ddd;">
                        <small class="text-muted">Add up to 5 tags to describe what your question is about.</small>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f1f1; padding: 20px 30px;">
                    <button type="button" class="theme-btn" data-bs-dismiss="modal"
                        style="background: transparent; border: 2px solid #ddd; color: #666;">{{ __('student.cancel') }}</button>
                    <button type="submit" class="theme-btn">{{ __('student.ask_question') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>