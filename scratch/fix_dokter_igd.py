import re

for fpath in ["plugins/dokter_igd/view/admin/form.soap.html", "plugins/dokter_igd/view/admin/soap.html"]:
    with open(fpath, "r") as f:
        content = f.read()
    new_content = re.sub(r"\[ADMIN,\s*['\"]update_bmt['\"]", "[ADMIN, 'form_rme'", content)
    new_content = new_content.replace("/update_bmt/", "/form_rme/")
    with open(fpath, "w") as f:
        f.write(new_content)
    print("Updated", fpath)
