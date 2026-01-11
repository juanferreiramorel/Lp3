<div class="card card-info">
    <div class="card-header py-1">
        <h3 class="card-title" style="font-size: 1rem;">Desglose de Impuestos (IVA)</h3>
    </div>
    <div class="card-body p-2">
        <div class="row">
            <!-- Left Column: Tax Bases (Gravadas) -->
            <div class="col-md-6 border-right">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td><strong>Total Exento (0%):</strong></td>
                        <td class="text-right"><span id="lbl_total_exenta">0</span> Gs.</td>
                    </tr>
                    <tr>
                        <td><strong>Total Gravado 5%:</strong></td>
                        <td class="text-right"><span id="lbl_base_5">0</span> Gs.</td>
                    </tr>
                    <tr>
                        <td><strong>Total Gravado 10%:</strong></td>
                        <td class="text-right"><span id="lbl_base_10">0</span> Gs.</td>
                    </tr>
                </table>
            </div>

             <!-- Right Column: Tax Amounts (Cuota IVA) -->
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                     <tr>
                        <td><strong>IVA 5%:</strong></td>
                        <td class="text-right"><span id="lbl_total_5">0</span> Gs.</td>
                    </tr>
                    <tr>
                        <td><strong>IVA 10%:</strong></td>
                        <td class="text-right"><span id="lbl_total_10">0</span> Gs.</td>
                    </tr>
                    <tr style="background-color: #e9ecef; border-top: 1px solid #dee2e6;">
                        <td><strong>Total IVA:</strong></td>
                        <td class="text-right"><strong><span id="lbl_total_iva">0</span> Gs.</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-12 text-right">
                 <h4 class="font-weight-bold">TOTAL GENERAL: <span id="lbl_total_general_footer">0</span> Gs.</h4>
            </div>
        </div>
    </div>
</div>
