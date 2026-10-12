import os

FORM_HTML = "/Applications/XAMPP/xamppfiles/htdocs/mlite-rsgm/plugins/update_bmt/view/admin/asesmen_medis_umum_irna/form.html"
with open(FORM_HTML, "r") as f:
    content = f.read()

# Fix imunisasi
old_imun = """                            <h5>RIWAYAT IMUNISASI</h5>
                            <div class="form-group form-inline">
                                <label class="checkbox-inline"><input type="checkbox" name="imunisasi_bcg" {if: $data.imunisasi_bcg == '1'}checked{/if}> BCG</label>&nbsp;&nbsp;
                                <label class="checkbox-inline"><input type="checkbox" name="imunisasi_polio" {if: $data.imunisasi_polio == '1'}checked{/if}> Polio</label>&nbsp;&nbsp;
                                <label class="checkbox-inline"><input type="checkbox" name="imunisasi_dpt" {if: $data.imunisasi_dpt == '1'}checked{/if}> DPT</label>&nbsp;&nbsp;
                                <label class="checkbox-inline"><input type="checkbox" name="imunisasi_campak" {if: $data.imunisasi_campak == '1'}checked{/if}> Campak</label>&nbsp;&nbsp;
                                <label class="checkbox-inline"><input type="checkbox" name="imunisasi_hepatitis_b" {if: $data.imunisasi_hepatitis_b == '1'}checked{/if}> Hepatitis B</label>&nbsp;&nbsp;
                                <label class="checkbox-inline"><input type="checkbox" name="imunisasi_lainnya" {if: $data.imunisasi_lainnya == '1'}checked{/if}> Lainnya</label>
                            </div>"""
new_imun = """                            <h5>RIWAYAT IMUNISASI</h5>
                            <div class="form-group">
                                <label class="checkbox-inline"><input type="checkbox" name="imunisasi_bcg" {if: $data.imunisasi_bcg == '1'}checked{/if}> BCG</label><br>
                                <label class="checkbox-inline"><input type="checkbox" name="chk_polio" {if: $data.imunisasi_polio}checked{/if}> Polio <input type="number" name="imunisasi_polio" class="form-control input-sm" style="width: 60px; display:inline-block;" value="{$data.imunisasi_polio}"> Kali</label><br>
                                <label class="checkbox-inline"><input type="checkbox" name="chk_dpt" {if: $data.imunisasi_dpt}checked{/if}> DPT <input type="number" name="imunisasi_dpt" class="form-control input-sm" style="width: 60px; display:inline-block;" value="{$data.imunisasi_dpt}"> Kali</label><br>
                                <label class="checkbox-inline"><input type="checkbox" name="chk_campak" {if: $data.imunisasi_campak}checked{/if}> Campak <input type="number" name="imunisasi_campak" class="form-control input-sm" style="width: 60px; display:inline-block;" value="{$data.imunisasi_campak}"> Kali</label><br>
                                <label class="checkbox-inline"><input type="checkbox" name="chk_hepb" {if: $data.imunisasi_hepatitis_b}checked{/if}> Hepatitis B <input type="number" name="imunisasi_hepatitis_b" class="form-control input-sm" style="width: 60px; display:inline-block;" value="{$data.imunisasi_hepatitis_b}"> Kali</label><br>
                                <label class="checkbox-inline"><input type="checkbox" name="chk_lainnya" {if: $data.imunisasi_lainnya}checked{/if}> Lainnya <input type="number" name="imunisasi_lainnya" class="form-control input-sm" style="width: 60px; display:inline-block;" value="{$data.imunisasi_lainnya}"> Kali</label>
                            </div>"""

if old_imun in content:
    content = content.replace(old_imun, new_imun)

# Fix TTD
old_ttd = """                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tanggal TTD</label>
                                <input type="date" name="tanggal_ttd" class="form-control" value="{$data.tanggal_ttd}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Jam TTD</label>
                                <input type="time" name="jam_ttd" class="form-control" value="{$data.jam_ttd}">
                            </div>
                        </div>"""
new_ttd = """                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Waktu TTD</label>
                                <input type="datetime-local" name="waktu_ttd" class="form-control" value="{if: $data.tanggal_ttd}{$data.tanggal_ttd}T{$data.jam_ttd}{/if}">
                            </div>
                        </div>"""

if old_ttd in content:
    content = content.replace(old_ttd, new_ttd)

with open(FORM_HTML, "w") as f:
    f.write(content)

print("form.html updated")
