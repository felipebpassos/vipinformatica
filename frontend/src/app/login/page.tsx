export default function LoginPage() {
    return (
        <div className="bg-white p-8 rounded-xl shadow-lg w-96">
            <h2 className="text-2xl font-bold text-gray-800 mb-6">Acesse sua conta</h2>
            <form className="space-y-4">
                <input
                    type="email"
                    placeholder="Email"
                    className="w-full p-3 border rounded-lg"
                />
                <input
                    type="password"
                    placeholder="Senha"
                    className="w-full p-3 border rounded-lg"
                />
                <button className="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700">
                    Entrar
                </button>
            </form>
        </div>
    )
}