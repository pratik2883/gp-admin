import zipfile
with zipfile.ZipFile('upload.zip', 'r') as zf:
    names = sorted(zf.namelist())
    for name in names[:100]:
        print(name)
    if len(names) > 100:
        print('... total entries:', len(names))
