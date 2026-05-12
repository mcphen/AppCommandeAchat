import tkinter as tk
from tkinter import ttk, messagebox
import bcrypt
import pyperclip

def generate_hash():
    password = entry_password.get()
    if not password:
        messagebox.showwarning("Attention", "Veuillez entrer un mot de passe.")
        return
    hashed = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt(rounds=12))
    result_var.set(hashed.decode('utf-8'))

def copy_hash():
    h = result_var.get()
    if h:
        try:
            pyperclip.copy(h)
            messagebox.showinfo("Copié", "Hash copié dans le presse-papiers.")
        except Exception:
            messagebox.showerror("Erreur", "Impossible de copier (pyperclip non disponible).")

def verify_hash():
    password = entry_password.get()
    h = result_var.get()
    if not password or not h:
        messagebox.showwarning("Attention", "Générez d'abord un hash.")
        return
    try:
        ok = bcrypt.checkpw(password.encode('utf-8'), h.encode('utf-8'))
        if ok:
            messagebox.showinfo("Vérification", "✅ Le mot de passe correspond au hash.")
        else:
            messagebox.showerror("Vérification", "❌ Le mot de passe ne correspond pas.")
    except Exception as e:
        messagebox.showerror("Erreur", str(e))

root = tk.Tk()
root.title("Hash::make() — Laravel Bcrypt Tool")
root.resizable(False, False)
root.configure(padx=20, pady=20)

tk.Label(root, text="Mot de passe :", font=("Segoe UI", 10)).grid(row=0, column=0, sticky="w")
entry_password = tk.Entry(root, width=40, show="●", font=("Segoe UI", 10))
entry_password.grid(row=0, column=1, columnspan=2, padx=(8, 0), pady=6)

btn_frame = tk.Frame(root)
btn_frame.grid(row=1, column=0, columnspan=3, pady=6)

tk.Button(btn_frame, text="Générer le Hash", command=generate_hash,
          bg="#4CAF50", fg="white", font=("Segoe UI", 10, "bold"), padx=10).pack(side="left", padx=4)
tk.Button(btn_frame, text="Vérifier", command=verify_hash,
          bg="#2196F3", fg="white", font=("Segoe UI", 10), padx=10).pack(side="left", padx=4)
tk.Button(btn_frame, text="Copier", command=copy_hash,
          bg="#FF9800", fg="white", font=("Segoe UI", 10), padx=10).pack(side="left", padx=4)

tk.Label(root, text="Hash Bcrypt (cost=12) :", font=("Segoe UI", 10)).grid(row=2, column=0, sticky="w", pady=(10,0))
result_var = tk.StringVar()
entry_result = tk.Entry(root, textvariable=result_var, width=60, font=("Courier New", 9), state="readonly")
entry_result.grid(row=3, column=0, columnspan=3, pady=6, ipady=4)

tk.Label(root, text="Compatible avec Hash::make() de Laravel (bcrypt, cost=12)",
         font=("Segoe UI", 8), fg="gray").grid(row=4, column=0, columnspan=3)

root.mainloop()
