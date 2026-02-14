<!-- Add Fee/Adjustment Modal -->
<div id="addFeeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Fee / Adjustment</h2>
            <button class="modal-close" onclick="closeAddFeeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addFeeForm">
                @csrf
                <input type="hidden" id="feeRentalId" name="rental_id">

                <div class="form-group">
                    <label for="feeType">Fee Type <span class="required">*</span></label>
                    <select id="feeType" name="type" class="form-input" required>
                        <option value="">Select type...</option>
                        <option value="Adjustment">Adjustment</option>
                        <option value="Damage">Damage Fee</option>
                        <option value="Loss">Loss Fee</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="feeAmount">Amount (₱) <span class="required">*</span></label>
                    <input type="number" id="feeAmount" name="amount" class="form-input" step="0.01" required>
                    <small class="form-hint">Use negative value for discount/adjustment</small>
                </div>

                <div class="form-group">
                    <label for="feeReason">Reason/Notes <span class="required">*</span></label>
                    <textarea id="feeReason" name="reason" class="form-input" rows="3" 
                              placeholder="Explain the reason for this fee or adjustment..." required></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddFeeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Add Fee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
