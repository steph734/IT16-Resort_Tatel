<!-- Return / Damage Assessment Modal -->
<div id="returnRentalModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Return / Damage Assessment</h2>
            <button class="modal-close" onclick="closeReturnModal()">X</button>
        </div>

        <div class="modal-body">
            <form id="returnRentalForm" enctype="multipart/form-data">
                @csrf

                <input type="hidden" id="returnRentalId" name="rental_id">

                <div class="form-row">

                    <div class="form-group">
                        <label for="returnedQuantity">Returned Quantity <span class="required">*</span></label>
                        <input type="number" id="returnedQuantity" name="returned_quantity" class="form-input"
                            readonly>

                        <small class="form-hint">
                            Total rented: <span id="totalRented">0</span>
                        </small>
                    </div>


                    <!-- Condition -->
                    <div class="form-group">
                        <label for="condition">Condition <span class="required">*</span></label>
                        <select id="condition" name="condition" class="form-input" required>
                            <option value="">Select condition...</option>
                            <option value="Good">Good Condition</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Lost">Lost</option>
                        </select>
                    </div>
                </div>

                <!-- DAMAGE / LOSS SECTION -->
                <div id="damageFields" style="display:none;">
                    <div class="alert alert-warning">
                        Please provide damage or loss details below
                    </div>

                    <div class="form-group">
                        <label for="damageDescription">Damage/Loss Description <span class="required">*</span></label>
                        <textarea id="damageDescription" name="damage_description" class="form-input" rows="3"
                            placeholder="Describe the damage or loss..."></textarea>
                    </div>

                    <!-- LOST/DAMAGE FEE -->
                    <div class="form-group">
                        <label>Lost/Damage Fee <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" id="lostDamageFee" name="lost_damage_fee" class="form-input">
                        </div>

                        <small class="form-hint">Auto-filled based on condition, but editable</small>
                    </div>

                    <!-- PHOTO -->
                    <div class="form-group">
                        <label for="damagePhoto">Upload Photo (Evidence)</label>
                        <input type="file" id="damagePhoto" name="photo" class="form-input" accept="image/*">
                    </div>
                </div>

                <div class="form-group">
                    <label for="returnNotes">Additional Notes</label>
                    <textarea id="returnNotes" name="notes" class="form-input" rows="2"
                        placeholder="Any additional notes..."></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeReturnModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Return</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    let rentalFees = { damage: 0, loss: 0 };

    async function loadRentalFees() {
        try {
            const response = await fetch("{{ url('/admin/rentals/rental-fees') }}");
            const data = await response.json();

            rentalFees.damage = data.find(f => f.type === "Damage")?.amount ?? 0;
            rentalFees.loss = data.find(f => f.type === "Loss")?.amount ?? 0;

        } catch (error) {
            console.error("Failed to load rental fees:", error);
        }
    }

    loadRentalFees();


    // 🔥 OPEN RETURN MODAL + LOAD RETURNED QUANTITY
    async function openReturnModal(rentalId) {

        document.getElementById('returnRentalId').value = rentalId;

        try {
            const response = await fetch(`/admin/rentals/${rentalId}/issued-quantity`);
            const data = await response.json();

            if (data.success) {
                const issuedQty = data.issued_quantity ?? 0;

                // Autofill and lock returned quantity
                document.getElementById("returnedQuantity").value = issuedQty;
                document.getElementById("returnedQuantity").readOnly = true;

                // Update UI
                document.getElementById("totalRented").innerText = issuedQty;
            }

        } catch (error) {
            console.error("Error loading issued qty:", error);
        }

        document.getElementById("returnRentalModal").classList.add('show');
    }

    // 🔥 CLOSE RETURN MODAL
    function closeReturnModal() {
        document.getElementById("returnRentalModal").classList.remove('show');
        document.getElementById("returnRentalForm").reset();
        document.getElementById('damageFields').style.display = 'none';
    }


    // 🔥 AUTO-FILL DAMAGE/LOSS FEES
    document.getElementById('condition')?.addEventListener('change', function () {
        const feeInput = document.getElementById('lostDamageFee');
        const damageFields = document.getElementById('damageFields');

        if (this.value === 'Damaged') {
            damageFields.style.display = "block";
            feeInput.value = rentalFees.damage;

        } else if (this.value === 'Lost') {
            damageFields.style.display = "block";
            feeInput.value = rentalFees.loss;

        } else {
            damageFields.style.display = "none";
            feeInput.value = "";
        }
    });
</script>