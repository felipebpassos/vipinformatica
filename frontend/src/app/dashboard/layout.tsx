// src/app/dashboard/layout.tsx
import React from "react";

const Layout = ({ children }: { children: React.ReactNode }) => {
    return (
        <div className="flex h-screen">
            {/* Sidebar */}
            <div className="w-64 bg-gray-800 text-white p-5">
                <h1 className="text-2xl font-bold">Dashboard</h1>
                <ul className="mt-6 space-y-4">
                    <li><a href="#" className="hover:text-gray-400">Home</a></li>
                    <li><a href="#" className="hover:text-gray-400">Settings</a></li>
                    <li><a href="#" className="hover:text-gray-400">Profile</a></li>
                    <li><a href="#" className="hover:text-gray-400">Messages</a></li>
                </ul>
            </div>

            {/* Main Content */}
            <div className="flex-1 bg-gray-100 p-6">
                <div className="bg-white p-4 rounded-lg shadow-md">
                    {/* Page Content */}
                    {children}
                </div>
            </div>
        </div>
    );
};

export default Layout;
